package com.proyecto.reportes.service;

import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.proyecto.reportes.dto.OrderReportResponse;
import com.proyecto.reportes.dto.SystemMetricsResponse;
import com.proyecto.reportes.dto.UserReportResponse;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.time.Instant;
import java.util.List;
import java.util.Map;

@Service
public class ReportService {

    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${usuarios.api.url}")
    private String usuariosApiUrl;

    @Value("${pedidos.api.url}")
    private String pedidosApiUrl;

    /**
     * ==============================
     *  REPORTE DE USUARIOS
     * ==============================
     */
    public UserReportResponse generateUserReport(String bearerToken) {

        HttpHeaders headers = new HttpHeaders();
        headers.set("Authorization", bearerToken);
        HttpEntity<Void> requestEntity = new HttpEntity<>(headers);

        ResponseEntity<List> resp = restTemplate.exchange(
                usuariosApiUrl + "/users",
                HttpMethod.GET,
                requestEntity,
                List.class
        );

        List<Map<String, Object>> users = resp.getBody();
        long totalUsers = users != null ? users.size() : 0;

        // Log en Firebase
        logReport("user_report", Map.of(
                "totalUsers", totalUsers,
                "createdAt", Instant.now().toString()
        ));

        UserReportResponse out = new UserReportResponse();
        out.setTotalUsers(totalUsers);
        out.setUsers(users);

        return out;
    }

    /**
     * ==============================
     *  REPORTE DE PEDIDOS
     * ==============================
     */
    public OrderReportResponse generateOrderReport(String bearerToken) {

        HttpHeaders headers = new HttpHeaders();
        headers.set("Authorization", bearerToken);
        HttpEntity<Void> requestEntity = new HttpEntity<>(headers);

        ResponseEntity<List> resp = restTemplate.exchange(
                pedidosApiUrl + "/orders",
                HttpMethod.GET,
                requestEntity,
                List.class
        );

        List<Map<String, Object>> orders = resp.getBody();
        long totalOrders = orders != null ? orders.size() : 0;

        double totalAmount = 0;
        if (orders != null) {
            for (Map<String, Object> o : orders) {
                Object price = o.get("price");
                if (price instanceof Number n) {
                    totalAmount += n.doubleValue();
                }
            }
        }

        // Log en Firebase
        logReport("order_report", Map.of(
                "totalOrders", totalOrders,
                "totalAmount", totalAmount,
                "createdAt", Instant.now().toString()
        ));

        OrderReportResponse out = new OrderReportResponse();
        out.setTotalOrders(totalOrders);
        out.setTotalAmount(totalAmount);
        out.setOrders(orders);

        return out;
    }

    /**
     * ==============================
     *  MÉTRICAS GENERALES
     * ==============================
     */
    public SystemMetricsResponse getSystemMetrics(String bearerToken) {

        UserReportResponse users = generateUserReport(bearerToken);
        OrderReportResponse orders = generateOrderReport(bearerToken);

        SystemMetricsResponse m = new SystemMetricsResponse();
        m.setTotalUsers(users.getTotalUsers());
        m.setTotalOrders(orders.getTotalOrders());
        m.setTotalRevenue(orders.getTotalAmount());
        return m;
    }

    /**
     * ==============================
     *  LOG EN FIREBASE
     * ==============================
     */
    private void logReport(String type, Map<String, Object> data) {
        try {
            DatabaseReference ref = FirebaseDatabase.getInstance()
                    .getReference("reports_logs")
                    .push();

            ref.setValueAsync(Map.of(
                    "type", type,
                    "data", data
            ));
        } catch (Exception ignored) {
        }
    }
}

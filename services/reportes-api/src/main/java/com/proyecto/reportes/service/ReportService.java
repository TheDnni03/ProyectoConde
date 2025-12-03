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
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Map;

@Service
public class ReportService {

    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${usuarios.api.url}")
    private String usuariosApiUrl;

    @Value("${pedidos.api.url}")
    private String pedidosApiUrl;

    // ============================================================
    // 1. REPORTE DE USUARIOS
    // ============================================================
    public UserReportResponse generateUserReport(String bearerToken) {

        HttpHeaders headers = new HttpHeaders();
        headers.set(HttpHeaders.AUTHORIZATION, bearerToken);
        HttpEntity<Void> requestEntity = new HttpEntity<>(headers);

        // Leemos como Object para soportar tanto [ ... ] como { ... }
        ResponseEntity<Object> resp = restTemplate.exchange(
                usuariosApiUrl + "/users",   // ajusta si tu endpoint es otro
                HttpMethod.GET,
                requestEntity,
                Object.class
        );

        Object body = resp.getBody();
        List<Map<String, Object>> users = extractListFromBody(body,
                "usuarios", "users", "data");

        long totalUsers = users.size();

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

    // ============================================================
    // 2. REPORTE DE PEDIDOS
    // ============================================================
    public OrderReportResponse generateOrderReport(String bearerToken) {

        HttpHeaders headers = new HttpHeaders();
        headers.set(HttpHeaders.AUTHORIZATION, bearerToken);
        HttpEntity<Void> requestEntity = new HttpEntity<>(headers);

        ResponseEntity<Object> resp = restTemplate.exchange(
                pedidosApiUrl + "/orders",   // ajusta si tu endpoint es otro
                HttpMethod.GET,
                requestEntity,
                Object.class
        );

        Object body = resp.getBody();
        List<Map<String, Object>> orders = extractListFromBody(body,
                "pedidos", "orders", "data");

        long totalOrders = orders.size();

        double totalAmount = 0.0;
        for (Map<String, Object> o : orders) {
            Object price = o.get("price");
            if (price instanceof Number n) {
                totalAmount += n.doubleValue();
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

    // ============================================================
    // 3. MÉTRICAS GENERALES
    // ============================================================
    public SystemMetricsResponse getSystemMetrics(String bearerToken) {

        UserReportResponse users = generateUserReport(bearerToken);
        OrderReportResponse orders = generateOrderReport(bearerToken);

        SystemMetricsResponse m = new SystemMetricsResponse();
        m.setTotalUsers(users.getTotalUsers());
        m.setTotalOrders(orders.getTotalOrders());
        m.setTotalRevenue(orders.getTotalAmount());
        return m;
    }

    // ============================================================
    // 4. LOG EN FIREBASE
    // ============================================================
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

    // ============================================================
    // 5. UTILIDAD PARA EXTRAER LISTAS SEGÚN LA FORMA DEL JSON
    // ============================================================
    @SuppressWarnings("unchecked")
    private List<Map<String, Object>> extractListFromBody(Object body, String... possibleKeys) {
        if (body == null) {
            return Collections.emptyList();
        }

        // Caso 1: el JSON era directamente un arreglo [ { ... }, { ... } ]
        if (body instanceof List<?> list) {
            List<Map<String, Object>> out = new ArrayList<>();
            for (Object elem : list) {
                if (elem instanceof Map<?, ?> m) {
                    out.add((Map<String, Object>) m);
                }
            }
            return out;
        }

        // Caso 2: el JSON es un objeto { ... } con una propiedad que contiene la lista
        if (body instanceof Map<?, ?> mapBody) {
            for (String key : possibleKeys) {
                Object inner = mapBody.get(key);
                if (inner instanceof List<?> list) {
                    List<Map<String, Object>> out = new ArrayList<>();
                    for (Object elem : list) {
                        if (elem instanceof Map<?, ?> m) {
                            out.add((Map<String, Object>) m);
                        }
                    }
                    return out;
                }
            }
        }

        return Collections.emptyList();
    }
}

package com.proyecto.reportes.controller;

import com.proyecto.reportes.dto.OrderReportResponse;
import com.proyecto.reportes.dto.SystemMetricsResponse;
import com.proyecto.reportes.dto.UserReportResponse;
import com.proyecto.reportes.service.ReportService;
import org.apache.commons.csv.CSVFormat;
import org.apache.commons.csv.CSVPrinter;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RestController;

import java.io.StringWriter;
import java.util.List;
import java.util.Map;

@RestController
public class ReportController {

    private final ReportService reportService;

    public ReportController(ReportService reportService) {
        this.reportService = reportService;
    }

    @GetMapping("/reports/users")
    public UserReportResponse usersReport(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.generateUserReport(authHeader);
    }

    @GetMapping("/reports/orders")
    public OrderReportResponse ordersReport(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.generateOrderReport(authHeader);
    }

    @GetMapping("/reports/metrics")
    public SystemMetricsResponse metrics(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.getSystemMetrics(authHeader);
    }

    // =============================
    // EXPORTAR CSV
    // =============================
    @GetMapping("/reports/export/{type}")
    public ResponseEntity<String> exportCsv(
            @PathVariable String type,
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader
    ) throws Exception {

        StringWriter out = new StringWriter();
        CSVPrinter csv;

        if ("users".equalsIgnoreCase(type)) {
            var rep = reportService.generateUserReport(authHeader);

            csv = new CSVPrinter(out,
                    CSVFormat.DEFAULT.withHeader("id", "email", "name"));

            if (rep.getUsers() != null) {
                for (Map<String, Object> u : rep.getUsers()) {

                    Object id = findByKeyFragment(u, "id", "uid");

                    Object email = findByKeyFragment(u,
                            "mail", "correo", "email");

                    Object name = findByKeyFragment(u,
                            "name", "nombre", "fullname", "displayname");

                    csv.printRecord(id, email, name);
                }
            }

        } else if ("orders".equalsIgnoreCase(type)) {
            var rep = reportService.generateOrderReport(authHeader);

            csv = new CSVPrinter(out,
                    CSVFormat.DEFAULT.withHeader("id", "product_id", "price", "address"));

            if (rep.getOrders() != null) {
                for (Map<String, Object> o : rep.getOrders()) {
                    csv.printRecord(
                            o.get("id"),
                            o.get("product_id"),
                            o.get("price"),
                            o.get("address")
                    );
                }
            }
        } else {
            return ResponseEntity.badRequest()
                    .body("Tipo de exportación inválido (usa users u orders)");
        }

        csv.flush();

        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION,
                        "attachment; filename=\"" + type + "_report.csv\"")
                .contentType(MediaType.TEXT_PLAIN)
                .body(out.toString());
    }

    // ============================================
    // Busca recursivamente un valor por fragmento
    // de nombre de llave (soporta anidado)
    // ============================================
    @SuppressWarnings("unchecked")
    private Object findByKeyFragment(Object obj, String... fragments) {
        if (obj == null) return null;

        // Si es directamente un valor simple
        if (!(obj instanceof Map<?, ?>) && !(obj instanceof List<?>)) {
            return null;
        }

        // Caso 1: Map
        if (obj instanceof Map<?, ?> map) {
            // primero revisamos las llaves de este nivel
            for (Map.Entry<?, ?> e : map.entrySet()) {
                String key = String.valueOf(e.getKey());
                String lk = key.toLowerCase();

                for (String f : fragments) {
                    if (lk.contains(f.toLowerCase())) {
                        return e.getValue();
                    }
                }
            }
            // después buscamos dentro de los valores (por si están anidados)
            for (Object value : map.values()) {
                Object found = findByKeyFragment(value, fragments);
                if (found != null) return found;
            }
        }

        // Caso 2: Lista (por si hay objetos dentro de arrays)
        if (obj instanceof List<?> list) {
            for (Object item : list) {
                Object found = findByKeyFragment(item, fragments);
                if (found != null) return found;
            }
        }

        return null;
    }
}

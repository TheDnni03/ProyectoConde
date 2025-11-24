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

@RestController
public class ReportController {

    private final ReportService reportService;

    public ReportController(ReportService reportService) {
        this.reportService = reportService;
    }

    // 1. Generar reporte de usuarios
    @GetMapping("/reports/users")
    public UserReportResponse usersReport(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.generateUserReport(authHeader);
    }

    // 2. Generar reporte de pedidos
    @GetMapping("/reports/orders")
    public OrderReportResponse ordersReport(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.generateOrderReport(authHeader);
    }

    // 3. Obtener métricas del sistema
    @GetMapping("/reports/metrics")
    public SystemMetricsResponse metrics(
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader,
            Authentication auth
    ) {
        return reportService.getSystemMetrics(authHeader);
    }

    // 4. Exportar datos a CSV
    @GetMapping("/reports/export/{type}")
    public ResponseEntity<String> exportCsv(
            @PathVariable String type,
            @RequestHeader(HttpHeaders.AUTHORIZATION) String authHeader
    ) throws Exception {

        StringWriter out = new StringWriter();
        CSVPrinter csv;

        if ("users".equalsIgnoreCase(type)) {
            var rep = reportService.generateUserReport(authHeader);
            csv = new CSVPrinter(out, CSVFormat.DEFAULT.withHeader("id", "email", "name"));

            if (rep.getUsers() != null) {
                for (var u : rep.getUsers()) {
                    csv.printRecord(
                            u.get("id"),
                            u.get("email"),
                            u.get("name")
                    );
                }
            }
        } else if ("orders".equalsIgnoreCase(type)) {
            var rep = reportService.generateOrderReport(authHeader);
            csv = new CSVPrinter(out, CSVFormat.DEFAULT.withHeader("id", "product_id", "price", "address"));
            if (rep.getOrders() != null) {
                for (var o : rep.getOrders()) {
                    csv.printRecord(
                            o.get("id"),
                            o.get("product_id"),
                            o.get("price"),
                            o.get("address")
                    );
                }
            }
        } else {
            return ResponseEntity.badRequest().body("Tipo de exportación inválido (usa users u orders)");
        }

        csv.flush();

        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION,
                        "attachment; filename=\"" + type + "_report.csv\"")
                .contentType(MediaType.TEXT_PLAIN)
                .body(out.toString());
    }
}

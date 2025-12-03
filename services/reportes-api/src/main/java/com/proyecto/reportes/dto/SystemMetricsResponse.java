package com.proyecto.reportes.dto;

public class SystemMetricsResponse {

    private long totalUsers;
    private long totalOrders;
    private double totalRevenue; // 👈 este es el nombre que usa ReportService

    public SystemMetricsResponse() {
    }

    public SystemMetricsResponse(long totalUsers, long totalOrders, double totalRevenue) {
        this.totalUsers = totalUsers;
        this.totalOrders = totalOrders;
        this.totalRevenue = totalRevenue;
    }

    public long getTotalUsers() {
        return totalUsers;
    }

    public void setTotalUsers(long totalUsers) {
        this.totalUsers = totalUsers;
    }

    public long getTotalOrders() {
        return totalOrders;
    }

    public void setTotalOrders(long totalOrders) {
        this.totalOrders = totalOrders;
    }

    public double getTotalRevenue() {          // ✔ usado por ReportService
        return totalRevenue;
    }

    public void setTotalRevenue(double totalRevenue) {   // ✔ usado por ReportService
        this.totalRevenue = totalRevenue;
    }
}

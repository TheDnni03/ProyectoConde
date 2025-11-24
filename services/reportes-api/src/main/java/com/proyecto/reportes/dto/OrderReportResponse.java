package com.proyecto.reportes.dto;

import java.util.List;
import java.util.Map;

public class OrderReportResponse {

    private long totalOrders;
    private double totalAmount;
    private List<Map<String, Object>> orders;

    public OrderReportResponse() {
    }

    public OrderReportResponse(long totalOrders, double totalAmount, List<Map<String, Object>> orders) {
        this.totalOrders = totalOrders;
        this.totalAmount = totalAmount;
        this.orders = orders;
    }

    public long getTotalOrders() {
        return totalOrders;
    }

    public void setTotalOrders(long totalOrders) {
        this.totalOrders = totalOrders;
    }

    public double getTotalAmount() {
        return totalAmount;
    }

    public void setTotalAmount(double totalAmount) {
        this.totalAmount = totalAmount;
    }

    public List<Map<String, Object>> getOrders() {
        return orders;
    }

    public void setOrders(List<Map<String, Object>> orders) {
        this.orders = orders;
    }
}

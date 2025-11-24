package com.proyecto.reportes.dto;

import java.util.List;
import java.util.Map;

public class UserReportResponse {

    private long totalUsers;
    private List<Map<String, Object>> users;

    public UserReportResponse() {
    }

    public UserReportResponse(long totalUsers, List<Map<String, Object>> users) {
        this.totalUsers = totalUsers;
        this.users = users;
    }

    public long getTotalUsers() {
        return totalUsers;
    }

    public void setTotalUsers(long totalUsers) {
        this.totalUsers = totalUsers;
    }

    public List<Map<String, Object>> getUsers() {
        return users;
    }

    public void setUsers(List<Map<String, Object>> users) {
        this.users = users;
    }
}

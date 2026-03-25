package com.sqliarena.hql.entity;

import jakarta.persistence.*;

@Entity
@Table(name = "secret_orders")
public class SecretOrder {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "customer_id", nullable = false)
    private Long customerId;

    @Column(nullable = false)
    private String product;

    @Column(nullable = false)
    private Double amount;

    @Column(nullable = false)
    private String status;

    @Column(name = "secret_flag")
    private String secretFlag;

    public SecretOrder() {}

    public SecretOrder(Long customerId, String product, Double amount, String status, String secretFlag) {
        this.customerId = customerId;
        this.product = product;
        this.amount = amount;
        this.status = status;
        this.secretFlag = secretFlag;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public Long getCustomerId() { return customerId; }
    public void setCustomerId(Long customerId) { this.customerId = customerId; }
    public String getProduct() { return product; }
    public void setProduct(String product) { this.product = product; }
    public Double getAmount() { return amount; }
    public void setAmount(Double amount) { this.amount = amount; }
    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }
    public String getSecretFlag() { return secretFlag; }
    public void setSecretFlag(String secretFlag) { this.secretFlag = secretFlag; }
}

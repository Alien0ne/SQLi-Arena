package com.sqliarena.hql.entity;

import jakarta.persistence.*;

@Entity
@Table(name = "internal_secrets")
public class InternalSecret {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "secret_key", nullable = false)
    private String secretKey;

    @Column(name = "secret_value", nullable = false)
    private String secretValue;

    public InternalSecret() {}

    public InternalSecret(String secretKey, String secretValue) {
        this.secretKey = secretKey;
        this.secretValue = secretValue;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getSecretKey() { return secretKey; }
    public void setSecretKey(String secretKey) { this.secretKey = secretKey; }
    public String getSecretValue() { return secretValue; }
    public void setSecretValue(String secretValue) { this.secretValue = secretValue; }
}

package com.sqliarena.hql.entity;

import jakarta.persistence.*;

@Entity
@Table(name = "secret_vault")
public class SecretVault {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "vault_key", nullable = false)
    private String vaultKey;

    @Column(name = "vault_value", nullable = false)
    private String vaultValue;

    public SecretVault() {}

    public SecretVault(String vaultKey, String vaultValue) {
        this.vaultKey = vaultKey;
        this.vaultValue = vaultValue;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getVaultKey() { return vaultKey; }
    public void setVaultKey(String vaultKey) { this.vaultKey = vaultKey; }
    public String getVaultValue() { return vaultValue; }
    public void setVaultValue(String vaultValue) { this.vaultValue = vaultValue; }
}

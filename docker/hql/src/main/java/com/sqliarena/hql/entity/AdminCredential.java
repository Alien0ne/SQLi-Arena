package com.sqliarena.hql.entity;

import jakarta.persistence.*;

@Entity
@Table(name = "admin_credentials")
public class AdminCredential {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String username;

    @Column(name = "password_hash", nullable = false)
    private String passwordHash;

    @Column(name = "secret_note")
    private String secretNote;

    public AdminCredential() {}

    public AdminCredential(String username, String passwordHash, String secretNote) {
        this.username = username;
        this.passwordHash = passwordHash;
        this.secretNote = secretNote;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getUsername() { return username; }
    public void setUsername(String username) { this.username = username; }
    public String getPasswordHash() { return passwordHash; }
    public void setPasswordHash(String passwordHash) { this.passwordHash = passwordHash; }
    public String getSecretNote() { return secretNote; }
    public void setSecretNote(String secretNote) { this.secretNote = secretNote; }
}

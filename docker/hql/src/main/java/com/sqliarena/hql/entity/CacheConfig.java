package com.sqliarena.hql.entity;

import jakarta.persistence.*;

@Entity
@Table(name = "cache_config")
public class CacheConfig {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "cache_name", nullable = false)
    private String cacheName;

    @Column(name = "cache_key", nullable = false)
    private String cacheKey;

    @Column(name = "cache_value", nullable = false)
    private String cacheValue;

    public CacheConfig() {}

    public CacheConfig(String cacheName, String cacheKey, String cacheValue) {
        this.cacheName = cacheName;
        this.cacheKey = cacheKey;
        this.cacheValue = cacheValue;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getCacheName() { return cacheName; }
    public void setCacheName(String cacheName) { this.cacheName = cacheName; }
    public String getCacheKey() { return cacheKey; }
    public void setCacheKey(String cacheKey) { this.cacheKey = cacheKey; }
    public String getCacheValue() { return cacheValue; }
    public void setCacheValue(String cacheValue) { this.cacheValue = cacheValue; }
}

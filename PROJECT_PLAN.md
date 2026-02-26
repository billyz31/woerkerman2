# 遊戲後端服務 - 現代化部署計畫書

## 專案概述

本專案旨在建立一個現代化的遊戲後端服務，包含完整的 API 服務、WebSocket 即時通訊、前端儀表板，以及標準的資料庫與快取層。

---

## 技術棧

| 層面 | 技術 | 版本 |
|------|------|------|
| 後端 API | Laravel | 11.x (PHP 8.3+) |
| WebSocket | Laravel Reverb / Workerman | 最新穩定版 |
| 前端框架 | React | 18.x |
| 構建工具 | Vite | 5.x |
| 程式語言 | TypeScript | 5.x |
| 資料庫 | MySQL | 8.0 |
| 快取 | Redis | 7.x |
| 反向代理 | Nginx | Alpine |
| 容器化 | Docker | 最新 |
| 部署平台 | Coolify | 自託管 |

---

## 系統架構

```
                    ┌─────────────────┐
                    │  Coolify Proxy  │
                    │   (Port 80/443) │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Nginx         │
                    │  (Container)    │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐        ┌─────▼─────┐       ┌─────▼─────┐
    │Frontend │        │  Laravel   │       │WebSocket  │
    │ :3000   │        │  API      │       │  :3001    │
    │         │        │  :8080    │       │           │
    └────┬────┘        └─────┬─────┘       └─────┬─────┘
         │                   │                   │
         │                   │                   │
    ┌────▼───────────────────▼───────────────────▼────┐
    │              Internal Network                   │
    │  ┌─────────┐    ┌─────────┐                  │
    │  │  MySQL  │    │  Redis  │                  │
    │  │  :3306  │    │  :6379  │                  │
    │  └─────────┘    └─────────┘                  │
    └──────────────────────────────────────────────┘
```

---

## 服務說明

### 1. Nginx（反向代理）

- **職責**：統一入口，路由轉發
- **端口**：80（對外）/ 內部 80
- **功能**：
  - `/` → Frontend
  - `/api/*` → Laravel API
  - `/socket.io/*` → WebSocket
  - `/health` → 健康檢查

### 2. Frontend（前端儀表板）

- **職責**：提供管理介面，測試 API 響應速度
- **技術**：React + Vite + TypeScript
- **端口**：3000（內部）
- **功能**：
  - 服務健康狀態儀表板
  - API 端點測試
  - WebSocket 連線測試
  - 錢包餘額管理
  - 遊戲測試功能

### 3. Laravel API（後端服務）

- **職責**：提供 RESTful API
- **技術**：Laravel 11
- **端口**：8080（內部）
- **功能**：
  - 使用者認證（JWT）
  - 錢包管理
  - 遊戲邏輯 API
  - 性能指標

### 4. WebSocket（即時通訊）

- **職責**：處理即時遊戲通訊
- **技術**：Laravel Reverb 或 Workerman
- **端口**：3001（內部）
- **功能**：
  - 即時消息推送
  - 遊戲狀態同步

### 5. MySQL（資料庫）

- **版本**：8.0
- **端口**：3306（內部）
- **初始化**：
  - schema_migrations 表
  - health_checks 表
  - player_wallets 表
  - wallet_transactions 表

### 6. Redis（快取）

- **版本**：7-alpine
- **端口**：6379（內部）
- **用途**：
  - Session 儲存
  - 快取
  - WebSocket 訊息隊列

---

## Coolify 部署配置

### 端口配置

| 服務 | 內部端口 | 外部端口 | 說明 |
|------|----------|----------|------|
| Nginx | 80 | **3000** | 統一入口（使用 3000 避免衝突） |
| Frontend | 3000 | - | 僅內部訪問 |
| Laravel | 8080 | - | 僅內部訪問 |
| WebSocket | 3001 | - | 僅內部訪問 |
| MySQL | 3306 | - | 僅內部訪問 |
| Redis | 6379 | - | 僅內部訪問 |

### 環境變數

```env
# 應用
APP_ENV=production
APP_DEBUG=false

# 資料庫
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=game
DB_USERNAME=game
DB_PASSWORD=<secret>

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<secret>

# JWT
JWT_SECRET=<secret>
JWT_TTL=3600

# 遊戲配置
SLOT_MIN_BET=1
SLOT_MAX_BET=1000
WALLET_CACHE_TTL=30
```

---

## API 端點規劃

### 健康檢查

| 端點 | 方法 | 說明 |
|------|------|------|
| `/health` | GET | 基礎健康檢查 |
| `/api/ping` | GET | API 響應測試 |
| `/api/db-check` | GET | 資料庫連線檢查 |
| `/api/db-health` | GET | 資料庫詳細狀態 |
| `/api/redis-check` | GET | Redis 連線檢查 |
| `/api/socket-check` | GET | WebSocket 連線檢查 |
| `/api/perf/metrics` | GET | 性能指標 |

### 認證

| 端點 | 方法 | 說明 |
|------|------|------|
| `/api/login` | POST | 玩家登入 |
| `/api/me` | GET | 取得當前玩家資訊 |

### 錢包

| 端點 | 方法 | 說明 |
|------|------|------|
| `/api/wallet/balance` | GET | 取得餘額 |
| `/api/wallet/credit` | POST | 入帳 |
| `/api/wallet/debit` | POST | 扣款 |

### 遊戲

| 端點 | 方法 | 說明 |
|------|------|------|
| `/api/slot/config` | GET | 取得老虎機配置 |
| `/api/slot/spin` | POST | 執行旋轉 |

---

## Nginx 路由配置

```nginx
server {
    listen 80;
    server_name _;

    # 前端靜態頁面
    location / {
        proxy_pass http://frontend:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # 健康檢查
    location /health {
        proxy_pass http://backend:8080;
        proxy_set_header Host $host;
    }

    # API 路由
    location /api/ {
        proxy_pass http://backend:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # WebSocket 路由
    location /socket.io/ {
        proxy_pass http://websocket:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 600;
        proxy_send_timeout 600;
    }
}
```

---

## 前端功能規劃

### 服務端點測試區
- [x] 顯示所有 API 端點狀態
- [x] 顯示響應時間
- [x] 顯示完整回應內容

### 性能指標區
- [x] 顯示最近請求記錄
- [x] 顯示方法、路徑、狀態、耗時

### 玩家驗證區
- [x] 玩家 ID 輸入
- [x] Secret 輸入
- [x] 登入按鈕
- [x] Token 顯示

### 錢包區
- [x] 查詢餘額
- [x] 入帳功能
- [x] 扣款功能

### 遊戲測試區
- [x] 老虎機 spin（API 方式）
- [x] 老虎機 spin（WebSocket 方式）
- [x] 顯示結果動畫
- [x] 顯示中獎金額

### WebSocket 測試區
- [x] 連線狀態顯示
- [x] 連線/斷線日誌
- [x] 即時消息顯示

---

## 部署檢查清單

### 部署前
- [ ] 確保 Coolify 伺服器 SSH 訪問正常
- [ ] 確保 3000 端口可用
- [ ] 準備網域（可選）

### 部署時
- [ ] 選擇正確的 Git 倉庫
- [ ] 設置端口為 3000
- [ ] 配置環境變數
- [ ] 配置網域（可選）

### 部署後
- [ ] 檢查所有容器運行狀態
- [ ] 訪問首頁顯示前端儀表板
- [ ] 測試 `/health` 端點
- [ ] 測試 `/api/ping` 端點
- [ ] 測試 WebSocket 連線
- [ ] 啟用 HTTPS（可選）

---

## 常見問題排查

### 端口衝突
- 確保 Coolify Proxy 未佔用目標端口
- 檢查是否有其他容器使用相同端口

### 容器無法啟動
- 檢查日誌：`docker logs <container_name>`
- 檢查環境變數是否正確
- 檢查網路連接

### 無法訪問應用
- 確認網域 DNS 指向正確
- 確認端口配置正確
- 確認 Nginx 路由配置正確

---

## 檔案結構

```
game-backend/
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   ├── Models/
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   ├── tests/
│   ├── Dockerfile
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── api/
│   │   ├── types/
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── public/
│   ├── Dockerfile
│   ├── package.json
│   ├── tsconfig.json
│   └── vite.config.ts
├── websocket/
│   ├── app/
│   ├── bootstrap/
│   ├── Dockerfile
│   ├── composer.json
│   └── start.php
├── nginx/
│   ├── Dockerfile
│   └── default.conf
├── mysql/
│   └── init/
│       └── 001_init.sql
├── redis/
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## 總結

本計畫書提供了完整的遊戲後端服務架構，包含：

1. **現代化技術棧** - Laravel 11 + React 18 + TypeScript
2. **完整基礎設施** - MySQL + Redis + Nginx
3. **統一入口架構** - 避免端口衝突問題
4. **詳細的 API 規劃** - 完整的健康檢查與遊戲 API
5. **友好的前端介面** - 可視化的服務監控與測試工具

按照本計畫書實施，可以建立一個穩定、可擴展的遊戲後端服務。

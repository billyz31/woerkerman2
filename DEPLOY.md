# 遊戲後端服務 - 完整部署指南

## 系統架構

```
Internet → Nginx (80/443) → Docker Containers
                              ├── frontend:3000 (靜態網頁)
                              ├── backend:8080  (API)
                              └── mysql:3306   (資料庫)
```

## 前置要求

- 伺服器（VPS）
- 網域（指向伺服器 IP）
- Docker & Docker Compose 已安裝

---

## 快速部署

### 步驟 1：克隆專案

```bash
git clone https://github.com/billyz31/woerkerman2.git
cd woerkerman2
```

### 步驟 2：建立環境變數檔案

```bash
cp .env.example .env
```

編輯 `.env` 檔案：

```env
MYSQL_ROOT_PASSWORD=your-secure-root-password
MYSQL_DATABASE=game
MYSQL_USER=game
MYSQL_PASSWORD=your-secure-game-password

DB_HOST=mysql
DB_DATABASE=game
DB_USERNAME=game
DB_PASSWORD=your-secure-game-password
```

### 步驟 3：啟動容器

```bash
docker compose up -d --build
```

### 步驟 4：驗證容器運行

```bash
docker ps
```

應該看到：
- frontend (port 3000)
- backend (port 8080)
- mysql (port 3306)

---

## Nginx 反向代理設定

### 步驟 1：建立 Nginx 配置

```bash
sudo nano /etc/nginx/sites-available/game-backend
```

貼上以下內容（替換 `your-domain.com` 為你的網域）：

```nginx
server {
    listen 80;
    server_name your-domain.com;

    # 前端靜態網頁
    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # API 路由
    location /api/ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 健康檢查
    location /health {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
    }
}
```

### 步驟 2：啟用配置

```bash
# 創建連結
sudo ln -s /etc/nginx/sites-available/game-backend /etc/nginx/sites-enabled/

# 測試配置
sudo nginx -t

# 重載 Nginx
sudo systemctl reload nginx
```

---

## Let's Encrypt HTTPS 設定

### 步驟 1：安裝 Certbot

```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx
```

### 步驟 2：申請 SSL 憑證

```bash
sudo certbot --nginx -d your-domain.com
```

按照指示輸入：
- Email 地址
- 同意服務條款
- 選擇是否接收新聞（可選）

### 步驟 3：自動續期測試

```bash
sudo certbot renew --dry-run
```

Certbot 會自動設定 cron job 來續期憑證。

---

## 常見指令

### 容器管理

```bash
# 啟動所有服務
docker compose up -d

# 停止所有服務
docker compose down

# 查看日誌
docker compose logs -f

# 重新建構（無快取）
docker compose build --no-cache
docker compose up -d

# 進入容器
docker exec -it backend sh
docker exec -it mysql sh
```

### 資料庫管理

```bash
# 連線到 MySQL
docker exec -it mysql mysql -u game -p game

# 匯入 SQL
docker exec -i mysql mysql -u game -p game < mysql/init/001_init.sql
```

---

## 防火牆設定

如果使用 UFW：

```bash
# 開放 SSH、HTTP、HTTPS
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443

# 啟用防火牆
sudo ufw enable

# 查看狀態
sudo ufw status
```

---

## 故障排除

### 容器無法啟動

```bash
# 查看容器日誌
docker compose logs backend
docker compose logs frontend
docker compose logs mysql
```

### 端口被佔用

```bash
# 查看端口使用情況
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
sudo netstat -tulpn | grep :3000
sudo netstat -tulpn | grep :8080

# 停止佔用端口的服務
sudo systemctl stop nginx
sudo systemctl stop apache2
```

### 資料庫連線錯誤

```bash
# 檢查 MySQL 容器
docker logs mysql

# 檢查環境變數
docker compose config
```

### Nginx 錯誤

```bash
# 測試配置
sudo nginx -t

# 查看錯誤日誌
sudo tail -f /var/log/nginx/error.log
```

---

## 生產環境建議

1. **使用 Docker Swarm 或 Kubernetes** 進行容器編排
2. **使用 Docker secrets** 管理敏感資訊
3. **設定備份** 定期備份 MySQL 資料
4. **監控系統** 使用 Prometheus + Grafana
5. **日誌管理** 使用 ELK Stack 或 Loki
6. **CDN** 使用 Cloudflare 加速靜態資源

---

## API 端點

部署成功後可存取：

| 端點 | 方法 | 說明 |
|------|------|------|
| `/health` | GET | 健康檢查 |
| `/api/ping` | GET | API 測試 |
| `/api/login` | POST | 玩家登入 |
| `/api/wallet/balance` | GET | 查詢餘額 |
| `/api/wallet/credit` | POST | 入帳 |
| `/api/wallet/debit` | POST | 扣款 |
| `/api/slot/config` | GET | 老虎機配置 |
| `/api/slot/spin` | POST | 老虎機旋轉 |

---

## 技術棧

| 項目 | 技術 |
|------|------|
| 前端 | React + Vite + TypeScript |
| 後端 | Plain PHP (Built-in Server) |
| 資料庫 | MySQL 8.0 |
| 反向代理 | Nginx |
| SSL | Let's Encrypt |
| 容器化 | Docker Compose |

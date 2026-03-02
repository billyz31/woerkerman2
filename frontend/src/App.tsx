import { useState } from "react";

interface Service {
  name: string;
  status: "pending" | "ok" | "error";
  time?: number;
  message?: string;
}

export default function App() {
  const [services, setServices] = useState<Service[]>([
    { name: "Frontend", status: "pending" },
    { name: "Backend (API)", status: "pending" },
    { name: "WebSocket", status: "pending" },
    { name: "MySQL", status: "pending" },
  ]);
  const [loading, setLoading] = useState(false);

  async function checkAll() {
    setLoading(true);
    setServices([
      { name: "Frontend", status: "pending" },
      { name: "Backend (API)", status: "pending" },
      { name: "WebSocket", status: "pending" },
      { name: "MySQL", status: "pending" },
    ]);

    const results: Service[] = [];

    // Frontend
    try {
      const start = performance.now();
      const res = await fetch("/");
      const time = Math.round(performance.now() - start);
      results.push({ name: "Frontend", status: res.ok ? "ok" : "error", time });
    } catch {
      results.push({ name: "Frontend", status: "error", message: "Connection failed" });
    }

    // Backend
    try {
      const start = performance.now();
      const res = await fetch("/health");
      const time = Math.round(performance.now() - start);
      const json = await res.json();
      results.push({ 
        name: "Backend (API)", 
        status: json.status === "ok" ? "ok" : "error",
        time,
        message: json.status === "ok" ? json.time : json.message 
      });
    } catch (e) {
      results.push({ name: "Backend (API)", status: "error", message: String(e) });
    }

    // WebSocket
    try {
      const start = performance.now();
      const ws = new WebSocket("ws://localhost:3001");
      await new Promise<void>((resolve, reject) => {
        ws.onopen = () => {
          const time = Math.round(performance.now() - start);
          results.push({ name: "WebSocket", status: "ok", time });
          ws.close();
          resolve();
        };
        ws.onerror = () => {
          results.push({ name: "WebSocket", status: "error" });
          reject();
        };
      });
    } catch {
      results.push({ name: "WebSocket", status: "error" });
    }

    // MySQL (via backend)
    try {
      const start = performance.now();
      const res = await fetch("/api/db-health");
      const time = Math.round(performance.now() - start);
      const json = await res.json();
      results.push({ 
        name: "MySQL", 
        status: json.success ? "ok" : "error",
        time,
        message: json.message || (json.success ? "Connected" : "Failed")
      });
    } catch (e) {
      results.push({ name: "MySQL", status: "error", message: String(e) });
    }

    setServices(results);
    setLoading(false);
  }

  return (
    <div style={{ fontFamily: "system-ui, sans-serif", maxWidth: 700, margin: "40px auto", padding: 20 }}>
      <h1 style={{ marginBottom: 20 }}>Service Status</h1>
      
      <button 
        onClick={checkAll} 
        disabled={loading}
        style={{
          padding: "12px 24px",
          fontSize: 16,
          background: loading ? "#ccc" : "#007bff",
          color: "white",
          border: "none",
          borderRadius: 4,
          cursor: loading ? "not-allowed" : "pointer",
          marginBottom: 20
        }}
      >
        {loading ? "Checking..." : "Check All Services"}
      </button>

      <table style={{ width: "100%", borderCollapse: "collapse" }}>
        <thead>
          <tr style={{ background: "#f5f5f5", textAlign: "left" }}>
            <th style={{ padding: 12 }}>Service</th>
            <th style={{ padding: 12 }}>Status</th>
            <th style={{ padding: 12 }}>Response Time</th>
            <th style={{ padding: 12 }}>Message</th>
          </tr>
        </thead>
        <tbody>
          {services.map(s => (
            <tr key={s.name} style={{ borderBottom: "1px solid #eee" }}>
              <td style={{ padding: 12 }}>{s.name}</td>
              <td style={{ padding: 12 }}>
                <span style={{
                  color: s.status === "ok" ? "green" : s.status === "error" ? "red" : "#999",
                  fontWeight: "bold"
                }}>
                  {s.status === "pending" ? "-" : s.status === "ok" ? "OK" : "ERROR"}
                </span>
              </td>
              <td style={{ padding: 12, color: s.time ? (s.time < 100 ? "green" : s.time < 500 ? "orange" : "red") : "#999" }}>
                {s.time !== undefined ? `${s.time}ms` : "-"}
              </td>
              <td style={{ padding: 12, color: "#666" }}>{s.message || "-"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

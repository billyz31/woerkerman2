import { useState, useRef, useEffect } from "react";
import { io, Socket } from "socket.io-client";
import type { ApiResponse, WalletBalance, WalletTx, SlotConfig, SlotSpin, PerfMetric } from "./types";

const endpoints = [
  "/health",
  "/api/ping",
  "/api/db-check",
  "/api/db-health",
  "/api/redis-check",
  "/api/socket-check",
  "/api/slot/config",
  "/api/perf/metrics"
];

export default function App() {
  const [rows, setRows] = useState<{ name: string; status: string; time: string; response: string }[]>(
    endpoints.map(n => ({ name: n, status: "Loading", time: "-", response: "" }))
  );
  const [log, setLog] = useState<string[]>([]);
  const [wsStatus, setWsStatus] = useState("Disconnected");
  const [wsEnabled, setWsEnabled] = useState(false);
  const [playerId, setPlayerId] = useState("player-001");
  const [secret, setSecret] = useState("dev-secret");
  const [token, setToken] = useState("");
  const [walletStatus, setWalletStatus] = useState("");
  const [balance, setBalance] = useState(0);
  const [amount, setAmount] = useState("100");
  const [ref, setRef] = useState("");
  const [slotBet, setSlotBet] = useState("10");
  const [slotStatus, setSlotStatus] = useState("");
  const [reels, setReels] = useState<string[]>(["-", "-", "-"]);
  const [win, setWin] = useState(0);
  const [round, setRound] = useState("-");
  const [slotSource, setSlotSource] = useState("-");
  const [slotLog, setSlotLog] = useState<string[]>([]);
  const [perfRows, setPerfRows] = useState<PerfMetric[]>([]);
  const [perfStatus, setPerfStatus] = useState("");
  const socketRef = useRef<Socket | null>(null);

  function addLog(msg: string) {
    setLog(prev => [`${new Date().toISOString()} ${msg}`, ...prev].slice(0, 200));
  }

  function addSlotLog(msg: string) {
    setSlotLog(prev => [`${new Date().toISOString()} ${msg}`, ...prev].slice(0, 200));
  }

  async function refreshEndpoints() {
    const newRows = [];
    for (const name of endpoints) {
      try {
        const start = performance.now();
        const res = await fetch(name);
        const text = await res.text();
        const ms = Math.round(performance.now() - start);
        newRows.push({ name, status: res.ok ? "OK" : "FAIL", time: `${ms}ms`, response: text.slice(0, 100) });
      } catch (e) {
        newRows.push({ name, status: "ERROR", time: "-", response: String(e) });
      }
    }
    setRows(newRows);
  }

  async function login() {
    setWalletStatus("Logging in...");
    const form = new URLSearchParams();
    form.set("playerId", playerId);
    form.set("secret", secret);
    const json = await fetch("/api/login", { method: "POST", body: form, headers: { "Content-Type": "application/x-www-form-urlencoded" } }).then(r => r.json()) as ApiResponse<{ token: string; playerId: string; role: string }>;
    if (json.success && json.data) {
      setToken(json.data.token);
      setWalletStatus("Logged in");
      addSlotLog(`login ${json.data.playerId}`);
    } else {
      setWalletStatus(json.message || "Failed");
    }
  }

  async function loadBalance() {
    setWalletStatus("Loading...");
    const json = await fetch("/api/wallet/balance", { headers: { "Authorization": `Bearer ${token}` } }).then(r => r.json()) as ApiResponse<WalletBalance>;
    if (json.success && json.data) {
      setBalance(json.data.balance);
      setWalletStatus("Done");
    } else {
      setWalletStatus(json.message || "Failed");
    }
  }

  async function walletOp(type: "credit" | "debit") {
    setWalletStatus("Processing...");
    const form = new URLSearchParams();
    form.set("amount", amount);
    if (ref) form.set("ref", ref);
    const json = await fetch(`/api/wallet/${type}`, { method: "POST", body: form, headers: { "Content-Type": "application/x-www-form-urlencoded", "Authorization": `Bearer ${token}` } }).then(r => r.json()) as ApiResponse<WalletTx>;
    if (json.success && json.data) {
      setBalance(json.data.balance);
      setWalletStatus("Done");
      addSlotLog(`${type} ${JSON.stringify(json.data)}`);
    } else {
      setWalletStatus(json.message || "Failed");
    }
  }

  async function loadSlotConfig() {
    const json = await fetch("/api/slot/config").then(r => r.json()) as ApiResponse<SlotConfig>;
    if (json.success) addSlotLog(`config ${JSON.stringify(json.data)}`);
  }

  async function spinApi() {
    setSlotStatus("Processing...");
    const form = new URLSearchParams();
    form.set("bet", slotBet);
    const json = await fetch("/api/slot/spin", { method: "POST", body: form, headers: { "Content-Type": "application/x-www-form-urlencoded", "Authorization": `Bearer ${token}` } }).then(r => r.json()) as ApiResponse<SlotSpin>;
    handleSpin(json, "api");
  }

  function handleSpin(json: ApiResponse<SlotSpin>, src: string) {
    if (json.success && json.data) {
      setReels(json.data.reels);
      setWin(json.data.win);
      setRound(json.data.roundId);
      setSlotSource(src);
      setBalance(json.data.balance);
      setSlotStatus("Done");
      addSlotLog(`${src} spin ${JSON.stringify(json.data)}`);
    } else {
      setSlotStatus(json.message || "Failed");
    }
  }

  function connectWs() {
    if (socketRef.current) socketRef.current.disconnect();
    const wsEndpoint = import.meta.env.VITE_WS_ENDPOINT || "";
    const socket = io(wsEndpoint || undefined, { path: "/socket.io", transports: ["websocket"], query: token ? { token } : undefined });
    socket.on("connect", () => { setWsStatus("Connected"); setWsEnabled(true); addLog("WS connected"); });
    socket.on("connect_error", (e: Error) => { setWsStatus("Error"); setWsEnabled(false); addLog(`WS error ${e.message}`); });
    socket.on("disconnect", () => { setWsStatus("Disconnected"); setWsEnabled(false); addLog("WS disconnected"); });
    socket.on("game_spin_result", (data: unknown) => handleSpin(data as ApiResponse<SlotSpin>, "socket"));
    socketRef.current = socket;
  }

  function wsSpin() {
    if (!socketRef.current) { setSlotStatus("Not connected"); return; }
    socketRef.current.emit("game_spin", { bet: Number(slotBet) || 0, token });
    setSlotStatus("Sent");
  }

  useEffect(() => { refreshEndpoints(); return () => { if (socketRef.current) socketRef.current.disconnect(); }; }, []);

  return (
    <div className="page">
      <header className="header">
        <div>
          <h1>Game Backend Dashboard</h1>
          <p className="muted">Laravel API + React + TypeScript</p>
        </div>
        <button onClick={refreshEndpoints}>Refresh</button>
      </header>

      <section className="card">
        <h2>Service Endpoints</h2>
        <table>
          <thead><tr><th>Endpoint</th><th>Status</th><th>Time</th><th>Response</th></tr></thead>
          <tbody>
            {rows.map(r => <tr key={r.name}><td>{r.name}</td><td>{r.status}</td><td>{r.time}</td><td className="mono">{r.response}</td></tr>)}
          </tbody>
        </table>
      </section>

      <section className="card">
        <h2>Performance Metrics</h2>
        <button onClick={async () => { setPerfStatus("Loading..."); const json = await fetch("/api/perf/metrics").then(r => r.json()); if (json.success) { setPerfRows(json.data?.recent || []); setPerfStatus("Done"); } }}>Load</button> <span className="muted">{perfStatus}</span>
        <table>
          <thead><tr><th>Time</th><th>Method</th><th>Path</th><th>Status</th><th>Duration</th></tr></thead>
          <tbody>
            {perfRows.length === 0 ? <tr><td colSpan={5}>No data</td></tr> : perfRows.map((r, i) => <tr key={i}><td>{r.time}</td><td>{r.method}</td><td>{r.path}</td><td>{r.success ? "OK" : r.code}</td><td>{Math.round(r.durationMs)}ms</td></tr>)}
          </tbody>
        </table>
      </section>

      <section className="grid">
        <div className="card">
          <h2>Player Auth</h2>
          <div className="row">
            <input value={playerId} onChange={e => setPlayerId(e.target.value)} placeholder="playerId" />
            <input value={secret} onChange={e => setSecret(e.target.value)} placeholder="secret" />
          </div>
          <div className="row">
            <button onClick={login}>Login</button>
            <span className="muted">{walletStatus}</span>
          </div>
          <div className="row"><textarea value={token} onChange={e => setToken(e.target.value)} placeholder="Bearer token" rows={2} /></div>
        </div>

        <div className="card">
          <h2>Wallet</h2>
          <div className="row">
            <button onClick={loadBalance}>Balance</button>
            <input value={amount} onChange={e => setAmount(e.target.value)} placeholder="amount" style={{ width: 80 }} />
            <input value={ref} onChange={e => setRef(e.target.value)} placeholder="ref" />
          </div>
          <div className="row">
            <button onClick={() => walletOp("credit")}>Credit</button>
            <button onClick={() => walletOp("debit")}>Debit</button>
            <span className="muted">Balance: {balance}</span>
          </div>
        </div>
      </section>

      <section className="card">
        <h2>Slot Game</h2>
        <div className="row">
          <input value={slotBet} onChange={e => setSlotBet(e.target.value)} placeholder="bet" style={{ width: 80 }} />
          <button onClick={spinApi}>API Spin</button>
          <button onClick={connectWs}>Connect WS</button>
          <button onClick={wsSpin} disabled={!wsEnabled}>WS Spin</button>
          <button onClick={loadSlotConfig}>Config</button>
          <span className="muted">{slotStatus}</span>
        </div>
        <div className="reels">{reels.map((r, i) => <div key={i} className="reel">{r}</div>)}</div>
        <div>Win: {win} | Round: {round} | Source: {slotSource}</div>
        <div className="log">{slotLog.join("\n")}</div>
      </section>

      <section className="card">
        <h2>WebSocket Log</h2>
        <div className="log">{log.join("\n")}</div>
      </section>
    </div>
  );
}

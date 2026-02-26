export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data?: T;
  code?: string;
}

export interface WalletBalance {
  playerId: string;
  balance: number;
  source?: string;
}

export interface WalletTx {
  playerId: string;
  balance: number;
  delta: number;
  ref: string;
  txId: string;
}

export interface SlotConfig {
  minBet: number;
  maxBet: number;
  symbols: string[];
  paylines: number;
  reels: number;
}

export interface SlotSpin {
  reels: string[];
  bet: number;
  win: number;
  balance: number;
  roundId: string;
}

export interface PerfMetric {
  time: string;
  method: string;
  path: string;
  success: boolean;
  code?: string;
  durationMs: number;
}

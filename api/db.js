// api/db.js - Database helper for Vercel Serverless Functions
const { Pool } = require("pg");

let pool = null;

function getPool() {
  const connectionString =
    process.env.POSTGRES_URL ||
    process.env.DATABASE_URL ||
    process.env.POSTGRES_PRISMA_URL;

  if (!connectionString) return null;

  if (!pool) {
    pool = new Pool({
      connectionString,
      ssl: {
        rejectUnauthorized: false
      },
      max: 5,
      idleTimeoutMillis: 30000,
      connectionTimeoutMillis: 5000
    });
  }
  return pool;
}

async function query(sql, params = []) {
  const p = getPool();
  if (p) {
    const client = await p.connect();
    try {
      const res = await client.query(sql, params);
      return res.rows;
    } finally {
      client.release();
    }
  }

  // Fallback ke Supabase REST jika POSTGRES_URL belum disetel
  return null;
}

module.exports = {
  getPool,
  query
};

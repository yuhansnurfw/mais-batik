// api/orders.js - Handler Pesanan untuk Vercel Serverless
const { supabaseFetch } = require("./db");

function checkAdmin(req) {
  const authKey = req.headers["x-admin-key"] || req.query.key;
  const expectedPassword = process.env.ADMIN_PASSWORD || "admin123";
  return authKey === expectedPassword || (authKey && authKey.startsWith("sb_"));
}

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, x-admin-key");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    // 1. GET: Ambil daftar pesanan (khusus admin)
    if (req.method === "GET") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak. Kata sandi admin diperlukan." });
      }

      const data = await supabaseFetch("orders?select=*&order=created_at.desc");
      return res.status(200).json(data);
    }

    // 2. POST: Buat pesanan baru (publik dari storefront)
    if (req.method === "POST") {
      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const {
        product_id,
        product_name,
        customer_name,
        customer_email,
        customer_phone,
        variant,
        quantity,
        unit_price,
        notes
      } = body;

      if (!customer_name || !customer_phone) {
        return res.status(400).json({ error: "Nama dan nomor telepon pelanggan wajib diisi." });
      }

      await supabaseFetch("orders", {
        method: "POST",
        headers: { Prefer: "return=minimal" },
        body: JSON.stringify({
          product_id: product_id ? Number(product_id) : null,
          product_name: product_name || "Batik",
          customer_name,
          customer_email: customer_email || "-",
          customer_phone,
          variant: variant || "-",
          quantity: Number(quantity) || 1,
          unit_price: Number(unit_price) || 0,
          status: "baru",
          notes: notes || ""
        })
      });

      return res.status(201).json({ success: true });
    }

    // 3. PATCH: Ubah status pesanan (admin)
    if (req.method === "PATCH") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const { id, status } = body;
      if (!id || !status) {
        return res.status(400).json({ error: "ID dan status pesanan diperlukan." });
      }

      await supabaseFetch(`orders?id=eq.${id}`, {
        method: "PATCH",
        headers: { Prefer: "return=minimal" },
        body: JSON.stringify({ status, updated_at: new Date().toISOString() })
      });

      return res.status(200).json({ success: true });
    }

    // 4. DELETE: Hapus pesanan (admin)
    if (req.method === "DELETE") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const id = body.id || req.query.id;
      if (!id) {
        return res.status(400).json({ error: "ID pesanan diperlukan." });
      }

      await supabaseFetch(`orders?id=eq.${id}`, { method: "DELETE" });
      return res.status(200).json({ success: true });
    }

    return res.status(405).json({ error: "Method not allowed" });
  } catch (err) {
    console.error("Orders error:", err);
    return res.status(500).json({ error: err.message });
  }
};

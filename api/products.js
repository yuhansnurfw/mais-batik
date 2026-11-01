// api/products.js - Handler Produk untuk Vercel Serverless
const { supabaseFetch } = require("./db");

function checkAdmin(req) {
  const authKey = req.headers["x-admin-key"] || req.query.key;
  const expectedPassword = process.env.ADMIN_PASSWORD || "admin123";
  return authKey === expectedPassword || (authKey && authKey.startsWith("sb_"));
}

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, x-admin-key");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    // 1. GET: Ambil daftar produk
    if (req.method === "GET") {
      const all = req.query.all === "true" || req.query.all === "1";
      const filter = all ? "" : "&status=eq.active";
      const data = await supabaseFetch(`products?select=*,categories(name,slug)${filter}&order=id.asc`);
      return res.status(200).json(data);
    }

    // 2. POST: Tambah produk baru (admin)
    if (req.method === "POST") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const { name, category_id, variant, image_path, price_min, price_max, stock, status } = body;

      if (!name || !category_id) {
        return res.status(400).json({ error: "Nama produk dan kategori wajib diisi." });
      }

      await supabaseFetch("products", {
        method: "POST",
        headers: { Prefer: "return=minimal" },
        body: JSON.stringify({
          name,
          category_id: Number(category_id),
          variant: variant || "-",
          image_path: image_path || "assets/images/placeholder.jpg",
          price_min: Number(price_min) || 0,
          price_max: Number(price_max) || Number(price_min) || 0,
          stock: Number(stock) || 0,
          status: status || "active"
        })
      });

      return res.status(201).json({ success: true });
    }

    // 3. DELETE: Hapus produk (admin)
    if (req.method === "DELETE") {
      if (!checkAdmin(req)) {
        return res.status(401).json({ error: "Akses ditolak." });
      }

      const body = typeof req.body === "string" ? JSON.parse(req.body) : (req.body || {});
      const id = body.id || req.query.id;
      if (!id) {
        return res.status(400).json({ error: "ID produk diperlukan." });
      }

      await supabaseFetch(`products?id=eq.${id}`, { method: "DELETE" });
      return res.status(200).json({ success: true });
    }

    return res.status(405).json({ error: "Method not allowed" });
  } catch (err) {
    console.error("Products error:", err);
    return res.status(500).json({ error: err.message });
  }
};

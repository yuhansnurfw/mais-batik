// api/categories.js - Handler Kategori untuk Vercel Serverless
const { supabaseFetch } = require("./db");

module.exports = async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  try {
    const data = await supabaseFetch("categories?select=*&order=id.asc");
    return res.status(200).json(data);
  } catch (err) {
    console.error("Categories error:", err);
    return res.status(500).json({ error: err.message });
  }
};

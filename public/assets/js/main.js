// Deteksi pembukaan file secara statis / file:/// untuk mencegah browser mendownload file PHP
const isSupabaseReady = () => Boolean(window.MaisBatikDB && window.MaisBatikDB.isConfigured());

if ((window.location.protocol === "file:" || window.location.port === "5500") && !isSupabaseReady()) {
  const serverBanner = document.createElement("div");
  serverBanner.setAttribute("role", "alert");
  serverBanner.style.cssText = "background:#8b2d1d;color:#fff;padding:12px 16px;text-align:center;font-size:14px;display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:12px;position:sticky;top:0;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,0.25);";
  serverBanner.innerHTML = `
    <span><strong>Server Aktif:</strong> Halaman ini dibuka via file:// tanpa Supabase. Jalankan di localhost (XAMPP) atau isi konfigurasi di <code>assets/js/supabase-config.js</code>.</span>
    <a href="http://localhost/mais-batik/public/" style="background:#fff;color:#8b2d1d;padding:5px 14px;border-radius:4px;font-weight:bold;text-decoration:none;">Buka di Localhost (XAMPP) ↗</a>
  `;
  document.body.prepend(serverBanner);

  document.querySelectorAll('a[href*="admin"], a[href$=".php"]').forEach((link) => {
    link.addEventListener("click", (e) => {
      if (window.location.protocol === "file:" && !isSupabaseReady()) {
        e.preventDefault();
        const target = link.getAttribute("href") ?? "";
        const destination = target.includes("admin")
          ? "http://localhost/mais-batik/public/admin/"
          : "http://localhost/mais-batik/public/" + target;
        alert("Aplikasi Mais Batik menggunakan backend server. Mengalihkan ke web server: " + destination);
        window.location.href = destination;
      }
    });
  });
}

const menuToggle = document.querySelector(".menu-toggle");
const navigation = document.querySelector("#primary-navigation");

if (menuToggle instanceof HTMLButtonElement && navigation instanceof HTMLElement) {
  const closeMenu = () => {
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Buka navigasi");
    navigation.classList.remove("is-open");
  };

  menuToggle.addEventListener("click", () => {
    const isExpanded = menuToggle.getAttribute("aria-expanded") === "true";
    menuToggle.setAttribute("aria-expanded", String(!isExpanded));
    menuToggle.setAttribute("aria-label", isExpanded ? "Buka navigasi" : "Tutup navigasi");
    navigation.classList.toggle("is-open", !isExpanded);
  });

  navigation.addEventListener("click", (event) => {
    if (event.target instanceof HTMLAnchorElement) {
      closeMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && menuToggle.getAttribute("aria-expanded") === "true") {
      closeMenu();
      menuToggle.focus();
    }
  });
}

const currentYear = document.querySelector("#current-year");

if (currentYear) {
  currentYear.textContent = String(new Date().getFullYear());
}

const productSearch = document.querySelector("#product-search");
const categoryFilter = document.querySelector("#category-filter");
const productCards = [...document.querySelectorAll(".product-card")];
const productCount = document.querySelector("#product-count");
const catalogEmpty = document.querySelector("#catalog-empty");

const filterProducts = () => {
  if (!(productSearch instanceof HTMLInputElement) || !(categoryFilter instanceof HTMLSelectElement)) {
    return;
  }

  const query = productSearch.value.trim().toLocaleLowerCase("id");
  const category = categoryFilter.value;
  let visibleCount = 0;

  for (const card of productCards) {
    const matchesCategory = category === "semua" || card.dataset.category === category;
    const matchesSearch = (card.dataset.search ?? "").toLocaleLowerCase("id").includes(query);
    const isVisible = matchesCategory && matchesSearch;

    card.hidden = !isVisible;
    visibleCount += Number(isVisible);
  }

  if (productCount) {
    productCount.textContent = `Menampilkan ${visibleCount} produk`;
  }

  if (catalogEmpty instanceof HTMLElement) {
    catalogEmpty.hidden = visibleCount > 0;
  }
};

productSearch?.addEventListener("input", filterProducts);
categoryFilter?.addEventListener("change", filterProducts);

const productDialog = document.querySelector("#product-dialog");
const dialogTitle = document.querySelector("#dialog-title");
const dialogDescription = document.querySelector("#dialog-description");
const dialogOrder = document.querySelector("#dialog-order");

const orderDialog = document.querySelector("#order-dialog");
const orderForm = document.querySelector("#order-form");
const orderDialogTitle = document.querySelector("#order-dialog-title");
const orderProductInfo = document.querySelector("#order-product-info");
const orderProductId = document.querySelector("#order-product-id");
const orderVariantInput = document.querySelector("#order-variant-input");
const orderQtyInput = document.querySelector("#order-qty-input");
const orderSubtotal = document.querySelector("#order-subtotal");
const orderAlert = document.querySelector("#order-alert");
const orderSubmitBtn = document.querySelector("#order-submit-btn");

const currencyFormat = new Intl.NumberFormat("id-ID");
let currentOrderPrice = 0;

const updateOrderSubtotal = () => {
  if (!orderSubtotal || !orderQtyInput) return;
  const qty = Math.max(1, parseInt(orderQtyInput.value, 10) || 1);
  const total = qty * currentOrderPrice;
  orderSubtotal.textContent = `Estimasi Total: Rp${currencyFormat.format(total)} (${qty} pcs)`;
};

const openOrderDialog = (productId, productName, variant, price, stock) => {
  if (!(orderDialog instanceof HTMLDialogElement)) return;

  if (productDialog instanceof HTMLDialogElement && productDialog.open) {
    productDialog.close();
  }

  currentOrderPrice = Number(price) || 0;
  if (orderDialogTitle) orderDialogTitle.textContent = `Pesan: ${productName}`;
  if (orderProductInfo) {
    orderProductInfo.textContent = `Harga satuan: Rp${currencyFormat.format(currentOrderPrice)} · Stok: ${stock}`;
  }
  if (orderProductId instanceof HTMLInputElement) orderProductId.value = productId;
  if (orderVariantInput instanceof HTMLInputElement) orderVariantInput.value = variant;
  if (orderQtyInput instanceof HTMLInputElement) {
    orderQtyInput.value = "1";
    orderQtyInput.max = String(Math.max(1, stock || 100));
  }
  if (orderAlert) {
    orderAlert.hidden = true;
    orderAlert.className = "order-alert";
    orderAlert.textContent = "";
  }
  if (orderSubmitBtn instanceof HTMLButtonElement) {
    orderSubmitBtn.disabled = false;
    orderSubmitBtn.textContent = "Kirim Pesanan →";
  }

  updateOrderSubtotal();
  orderDialog.showModal();
};

orderQtyInput?.addEventListener("input", updateOrderSubtotal);

const bindProductCardEvents = () => {
  document.querySelectorAll("[data-order-trigger]").forEach((button) => {
    button.onclick = () => {
      openOrderDialog(
        button.dataset.productId ?? "1",
        button.dataset.productName ?? "Batik",
        button.dataset.variant ?? "",
        Number(button.dataset.price ?? 0),
        Number(button.dataset.stock ?? 10)
      );
    };
  });

  document.querySelectorAll(".product-detail").forEach((button) => {
    button.onclick = () => {
      const productName = button.dataset.product;
      const description = button.dataset.description;
      const productCard = button.closest(".product-card");
      const orderBtn = productCard?.querySelector("[data-order-trigger]");

      if (!productName || !description || !(productCard instanceof HTMLElement)) {
        return;
      }

      if (dialogTitle) dialogTitle.textContent = productName;
      if (dialogDescription) dialogDescription.textContent = description;

      if (dialogOrder && orderBtn instanceof HTMLElement) {
        dialogOrder.removeAttribute("aria-disabled");
        dialogOrder.classList.remove("is-disabled");
        dialogOrder.textContent = "Pesan Produk";
        dialogOrder.dataset.productId = orderBtn.dataset.productId ?? "1";
        dialogOrder.dataset.productName = orderBtn.dataset.productName ?? productName;
        dialogOrder.dataset.variant = orderBtn.dataset.variant ?? "";
        dialogOrder.dataset.price = orderBtn.dataset.price ?? "0";
        dialogOrder.dataset.stock = orderBtn.dataset.stock ?? "10";
      } else if (dialogOrder) {
        dialogOrder.setAttribute("aria-disabled", "true");
        dialogOrder.classList.add("is-disabled");
        dialogOrder.textContent = "Stok habis";
      }
      productDialog?.showModal();
    };
  });
};

bindProductCardEvents();

if (dialogOrder instanceof HTMLElement) {
  dialogOrder.addEventListener("click", () => {
    if (dialogOrder.classList.contains("is-disabled")) return;
    openOrderDialog(
      dialogOrder.dataset.productId ?? "1",
      dialogOrder.dataset.productName ?? "Batik",
      dialogOrder.dataset.variant ?? "",
      Number(dialogOrder.dataset.price ?? 0),
      Number(dialogOrder.dataset.stock ?? 10)
    );
  });
}

if (productDialog instanceof HTMLDialogElement) {
  productDialog.addEventListener("click", (event) => {
    if (event.target === productDialog) {
      productDialog.close();
    }
  });

  productDialog.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      event.preventDefault();
      productDialog.close();
    }
  });
}

if (orderDialog instanceof HTMLDialogElement && orderForm instanceof HTMLFormElement) {
  orderDialog.addEventListener("click", (event) => {
    if (event.target === orderDialog) {
      orderDialog.close();
    }
  });

  orderDialog.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      event.preventDefault();
      orderDialog.close();
    }
  });

  orderForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!orderAlert || !(orderSubmitBtn instanceof HTMLButtonElement)) return;

    orderSubmitBtn.disabled = true;
    orderSubmitBtn.textContent = "Menyimpan ke database...";
    orderAlert.hidden = true;

    try {
      if (isSupabaseReady()) {
        const orderData = {
          product_id: orderProductId?.value || "1",
          product_name: orderDialogTitle?.textContent?.replace(/^Pesan:\s*/, "") || "Batik",
          customer_name: orderForm.elements.namedItem("customer_name")?.value,
          customer_phone: orderForm.elements.namedItem("customer_phone")?.value,
          customer_email: orderForm.elements.namedItem("customer_email")?.value,
          variant: orderVariantInput?.value || "",
          quantity: orderQtyInput?.value || "1",
          unit_price: currentOrderPrice,
          notes: orderForm.elements.namedItem("notes")?.value || "",
        };

        await window.MaisBatikDB.createOrder(orderData);
        orderAlert.className = "order-alert success";
        orderAlert.textContent = "Pesanan berhasil disimpan ke database Supabase! Tim kami akan segera menghubungi Anda.";
        orderAlert.hidden = false;
        orderForm.reset();
        orderSubmitBtn.textContent = "Pesanan Terkirim ✓";
        return;
      }

      const formData = new FormData(orderForm);
      const res = await fetch("pesan.php", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const data = await res.json();
      if (res.ok && data.success) {
        orderAlert.className = "order-alert success";
        orderAlert.textContent = data.message || "Pesanan berhasil disimpan di database!";
        orderAlert.hidden = false;
        orderForm.reset();
        orderSubmitBtn.textContent = "Pesanan Terkirim ✓";
      } else {
        throw new Error(data.message || "Gagal menyimpan pesanan.");
      }
    } catch (err) {
      orderAlert.className = "order-alert error";
      orderAlert.textContent = err.message || "Terjadi kesalahan saat menyimpan pesanan.";
      orderAlert.hidden = false;
      orderSubmitBtn.disabled = false;
      orderSubmitBtn.textContent = "Kirim Pesanan →";
    }
  });
}

// Sinkronisasi otomatis dengan database Supabase jika kredensial sudah diisi
if (isSupabaseReady()) {
  (async () => {
    try {
      const [categories, products] = await Promise.all([
        window.MaisBatikDB.getCategories(),
        window.MaisBatikDB.getProducts(),
      ]);

      if (categoryFilter instanceof HTMLSelectElement && categories.length > 0) {
        categoryFilter.innerHTML = '<option value="semua">Semua kategori</option>';
        categories.forEach((c) => {
          const opt = document.createElement("option");
          opt.value = c.slug;
          opt.textContent = c.name;
          categoryFilter.appendChild(opt);
        });
      }

      const grid = document.querySelector("#product-grid");
      if (grid instanceof HTMLElement && products.length > 0) {
        grid.innerHTML = products
          .map((p) => {
            const inStock = Number(p.stock) > 0;
            const searchStr = `${p.name} ${p.variant} ${p.category_name}`.toLowerCase();
            const priceText = `Rp${currencyFormat.format(p.price_min)} – Rp${currencyFormat.format(p.price_max)}`;
            return `
              <article class="product-card" data-category="${p.category}" data-search="${searchStr}">
                <div class="product-image">
                  <img src="${p.image_path}" alt="${p.name}" width="640" height="720" loading="lazy">
                  <span class="product-category">${p.category_name}</span>
                  <span class="stock-badge ${inStock ? (p.stock <= 5 ? "stock-limited" : "") : "stock-sold-out"}">
                    <span aria-hidden="true"></span> ${inStock ? `Stok tersedia · ${p.stock}` : "Stok habis"}
                  </span>
                </div>
                <div class="product-info">
                  <p class="product-variant">${p.variant}</p>
                  <h3>${p.name}</h3>
                  <p class="product-price">${priceText}</p>
                  <div class="product-actions">
                    ${
                      inStock
                        ? `<button class="product-order" type="button" data-order-trigger
                            data-product-id="${p.id}"
                            data-product-name="${p.name}"
                            data-variant="${p.variant}"
                            data-price="${p.price_min}"
                            data-stock="${p.stock}">Pesan <span aria-hidden="true">→</span></button>`
                        : '<span class="product-order is-disabled" aria-disabled="true">Stok habis</span>'
                    }
                    <button class="product-detail" type="button" data-product="${p.name}" data-description="${p.category_name}. Varian: ${p.variant}. ${inStock ? `Stok tersedia (${p.stock})` : "Stok habis"}.">Detail</button>
                  </div>
                </div>
              </article>
            `;
          })
          .join("");

        bindProductCardEvents();
        filterProducts();
      }

      const note = document.querySelector("#catalog-db-status") || document.querySelector(".catalog-note");
      if (note) {
        note.innerHTML = "Database Terhubung<br><strong>Supabase Cloud</strong>";
      }
    } catch (e) {
      console.warn("Gagal sinkronisasi Supabase:", e);
    }
  })();
}

// Cek notifikasi sukses dari URL
if (new URLSearchParams(window.location.search).get("order_success") === "1") {
  const successNotice = document.createElement("div");
  successNotice.style.cssText =
    "background:#046c4e;color:#fff;padding:12px 16px;text-align:center;font-size:14px;position:sticky;top:0;z-index:9999;";
  successNotice.innerHTML =
    "✓ <strong>Pesanan Berhasil:</strong> Pesanan Anda telah tersimpan di sistem kami. Tim Mais Batik akan segera menghubungi Anda.";
  document.body.prepend(successNotice);
}

const validatePriceRange = (form) => {
  const minimum = form.elements.namedItem("price_min");
  const maximum = form.elements.namedItem("price_max");

  if (!(minimum instanceof HTMLInputElement) || !(maximum instanceof HTMLInputElement)) {
    return true;
  }

  maximum.setCustomValidity(
    Number(maximum.value) >= Number(minimum.value)
      ? ""
      : "Harga maksimum tidak boleh kurang dari harga minimum."
  );

  return form.reportValidity();
};

document.querySelectorAll(".product-form").forEach((form) => {
  form.addEventListener("input", (event) => {
    if (event.target instanceof HTMLInputElement && event.target.name === "price_min") {
      const maximum = form.elements.namedItem("price_max");
      if (maximum instanceof HTMLInputElement) {
        maximum.setCustomValidity("");
      }
    }
  });

  form.addEventListener("submit", (event) => {
    if (!validatePriceRange(form)) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll(".order-form [data-product-select]").forEach((select) => {
  const updateProductDefaults = () => {
    if (!(select instanceof HTMLSelectElement)) {
      return;
    }

    const option = select.selectedOptions[0];
    const form = select.form;
    const variant = form?.querySelector("[data-order-variant]");
    const price = form?.querySelector("[data-order-price]");

    if (variant instanceof HTMLInputElement && option?.dataset.variant) {
      variant.value = option.dataset.variant;
    }

    if (price instanceof HTMLInputElement && option?.dataset.price) {
      price.value = option.dataset.price;
    }
  };

  select.addEventListener("change", updateProductDefaults);
});

const posForm = document.querySelector("[data-pos-form]");

if (posForm instanceof HTMLFormElement) {
  const rowsContainer = posForm.querySelector("[data-pos-items]");
  const addItemButton = posForm.querySelector("[data-add-pos-item]");
  const totalOutput = posForm.querySelector("[data-pos-total]");
  const currencyFormatter = new Intl.NumberFormat("id-ID");

  const updatePosTotals = () => {
    if (!(rowsContainer instanceof HTMLElement)) {
      return;
    }

    let total = 0;
    const rows = [...rowsContainer.querySelectorAll("[data-pos-row]")];
    rows.forEach((row, index) => {
      const select = row.querySelector("[data-pos-product-select]");
      const quantity = row.querySelector("[data-pos-quantity]");
      const lineOutput = row.querySelector("[data-pos-line-total]");
      const removeButton = row.querySelector("[data-remove-pos-item]");

      if (
        !(select instanceof HTMLSelectElement)
        || !(quantity instanceof HTMLInputElement)
        || !(lineOutput instanceof HTMLElement)
      ) {
        return;
      }

      select.name = `items[${index}][product_id]`;
      quantity.name = `items[${index}][quantity]`;
      if (removeButton instanceof HTMLButtonElement) {
        removeButton.disabled = rows.length === 1;
      }

      const option = select.selectedOptions[0];
      const price = Number(option?.dataset.price);
      const stock = Number(option?.dataset.stock);
      const count = Number(quantity.value);

      if (select.value === "" || !Number.isSafeInteger(price) || !Number.isSafeInteger(count)) {
        lineOutput.textContent = "Pilih produk untuk melihat subtotal.";
        return;
      }

      const subtotal = price * count;
      quantity.max = String(stock);
      lineOutput.textContent = `Subtotal: Rp${currencyFormatter.format(subtotal)} (stok ${stock})`;
      total += subtotal;
    });

    if (totalOutput instanceof HTMLElement) {
      totalOutput.textContent = Number.isSafeInteger(total)
        ? `Total: Rp${currencyFormatter.format(total)}`
        : "Total melebihi batas nilai yang dapat ditampilkan.";
    }
  };

  rowsContainer?.addEventListener("change", updatePosTotals);
  rowsContainer?.addEventListener("input", updatePosTotals);
  rowsContainer?.addEventListener("click", (event) => {
    if (!(event.target instanceof Element) || !(rowsContainer instanceof HTMLElement)) {
      return;
    }

    const removeButton = event.target.closest("[data-remove-pos-item]");
    if (removeButton instanceof HTMLButtonElement) {
      removeButton.closest("[data-pos-row]")?.remove();
      updatePosTotals();
    }
  });

  addItemButton?.addEventListener("click", () => {
    const templateRow = rowsContainer?.querySelector("[data-pos-row]");
    if (!(templateRow instanceof HTMLElement) || !(rowsContainer instanceof HTMLElement)) {
      return;
    }

    const row = templateRow.cloneNode(true);
    if (!(row instanceof HTMLElement)) {
      return;
    }

    const select = row.querySelector("[data-pos-product-select]");
    const quantity = row.querySelector("[data-pos-quantity]");
    if (select instanceof HTMLSelectElement) {
      select.selectedIndex = 0;
    }
    if (quantity instanceof HTMLInputElement) {
      quantity.value = "1";
      quantity.max = "1000000";
    }

    rowsContainer.append(row);
    updatePosTotals();
  });

  updatePosTotals();
}

const deleteDialog = document.querySelector("#delete-dialog");
const deleteForm = document.querySelector("#delete-form");
const deleteAction = document.querySelector("#delete-action");
const deleteId = document.querySelector("#delete-id");
const deleteMessage = document.querySelector("#delete-message");

if (
  deleteDialog instanceof HTMLDialogElement &&
  deleteForm instanceof HTMLFormElement &&
  deleteAction instanceof HTMLInputElement &&
  deleteId instanceof HTMLInputElement &&
  deleteMessage instanceof HTMLElement
) {
  document.querySelectorAll(".delete-request").forEach((button) => {
    button.addEventListener("click", () => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }

      const recordId = button.dataset.recordId;
      const recordType = button.dataset.recordType;
      const recordName = button.dataset.recordName;

      if (!recordId || !recordType || !recordName) {
        return;
      }

      const deleteActions = {
        produk: "delete_product",
        pesanan: "delete_order",
        kategori: "delete_category",
      };
      const action = deleteActions[recordType];
      if (!action) {
        return;
      }

      deleteAction.value = action;
      deleteId.value = recordId;
      deleteMessage.textContent = `Yakin ingin menghapus ${recordType} “${recordName}”? Tindakan ini tidak dapat dibatalkan.`;
      deleteDialog.showModal();
    });
  });

  document.querySelector("[data-close-dialog]")?.addEventListener("click", () => {
    deleteDialog.close();
  });

  deleteDialog.addEventListener("click", (event) => {
    if (event.target === deleteDialog) {
      deleteDialog.close();
    }
  });
}

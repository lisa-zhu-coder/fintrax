/* Miramira · Dashboard financiero (MVP sin backend) */
let STORES = [
  { id: "luz_del_tajo", name: "Miramira - Luz del Tajo" },
  { id: "maquinista", name: "Miramira - Maquinista" },
  { id: "puerto_venecia", name: "Miramira - Puerto Venecia" },
  { id: "xanadu", name: "Miramira - Xanadu" },
];

// Función para cargar tiendas desde companyManager
function loadStoresFromCompany() {
  if (typeof companyManager !== "undefined" && companyManager) {
    const companyData = companyManager.getCompanyData();
    if (companyData && companyData.businesses && companyData.businesses.length > 0) {
      // Convertir businesses a formato STORES
      STORES = companyData.businesses.map(business => ({
        id: business.id || `business-${Date.now()}-${Math.random()}`,
        name: business.name || "Sin nombre"
      }));
      return true;
    }
  }
  return false;
}

// Función para actualizar todos los selects de tiendas
function refreshAllStoreSelects() {
  // Recargar tiendas desde companyManager
  loadStoresFromCompany();
  
  // Actualizar select principal de tiendas
  if (ui.storeSelect) {
    const currentValue = ui.storeSelect.value;
    const availableStores = getAvailableStores();
    setSelectOptions(ui.storeSelect, availableStores, availableStores.length === STORES.length);
    // Restaurar valor si existe
    if (currentValue && availableStores.find(s => s.id === currentValue)) {
      ui.storeSelect.value = currentValue;
    }
  }
  
  // Actualizar select de tienda en formulario
  if (ui.formStore) {
    const currentValue = ui.formStore.value;
    const availableStores = getAvailableStores();
    setSelectOptions(ui.formStore, availableStores, false);
    // Restaurar valor si existe
    if (currentValue && availableStores.find(s => s.id === currentValue)) {
      ui.formStore.value = currentValue;
    }
  }
  
  // Actualizar filtros de ingresos
  if (ui.filterIngresosTienda) {
    const currentValue = ui.filterIngresosTienda.value;
    ui.filterIngresosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const availableStores = getAvailableStores();
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterIngresosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterIngresosTienda.value = currentValue;
  }
  
  // Actualizar filtros de gastos
  if (ui.filterGastosTienda) {
    const currentValue = ui.filterGastosTienda.value;
    ui.filterGastosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const availableStores = getAvailableStores();
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterGastosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterGastosTienda.value = currentValue;
  }
  
  // Actualizar filtros de cierres diarios
  if (ui.filterCierresDiariosTienda) {
    const currentValue = ui.filterCierresDiariosTienda.value;
    ui.filterCierresDiariosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const availableStores = getAvailableStores();
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterCierresDiariosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterCierresDiariosTienda.value = currentValue;
  }
  
  // Actualizar filtros de papelera
  if (ui.filterTrashStore) {
    const currentValue = ui.filterTrashStore.value;
    ui.filterTrashStore.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const availableStores = getAvailableStores();
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterTrashStore.appendChild(opt);
    }
    if (currentValue) ui.filterTrashStore.value = currentValue;
  }
  
  // Actualizar en orderManager si existe
  if (typeof orderManager !== "undefined" && orderManager && typeof orderManager.populateStoreFilter === "function") {
    orderManager.populateStoreFilter();
  }
  
  // Actualizar en expense split si está visible
  if (ui.formExpenseSplitStores && ui.formExpenseSplitStores.checked) {
    renderExpenseSplitStores();
  }
}

// Cargar tiendas al inicio
loadStoresFromCompany();
const STORAGE_KEY = "miramira_financial_entries_v1";
const TRASH_STORAGE_KEY = "miramira_financial_entries_trash_v1";
const TRASH_RETENTION_DAYS = 30;

// Denominaciones de billetes y monedas (EUR)
const DENOMINATIONS = [
  { type: "billete", value: 500, label: "500 €" },
  { type: "billete", value: 200, label: "200 €" },
  { type: "billete", value: 100, label: "100 €" },
  { type: "billete", value: 50, label: "50 €" },
  { type: "billete", value: 20, label: "20 €" },
  { type: "billete", value: 10, label: "10 €" },
  { type: "billete", value: 5, label: "5 €" },
  { type: "moneda", value: 2, label: "2 €" },
  { type: "moneda", value: 1, label: "1 €" },
  { type: "moneda", value: 0.5, label: "0,50 €" },
  { type: "moneda", value: 0.2, label: "0,20 €" },
  { type: "moneda", value: 0.1, label: "0,10 €" },
  { type: "moneda", value: 0.05, label: "0,05 €" },
  { type: "moneda", value: 0.02, label: "0,02 €" },
  { type: "moneda", value: 0.01, label: "0,01 €" },
];

/** @typedef {{id:string, concept:string, amount:number, paidWithCash?:boolean}} ExpenseItem */
/** @typedef {{id:string, storeId:string, date:string, entryType:"daily_close"|"expense"|"income"|"expense_refund", sales:number, expenses:number, notes:string, cashInitial:number, tpv:number, cashExpenses:number, cashCount:Record<string,number>, shopifyCash:number|null, shopifyTpv:number|null, vouchersIn:number, vouchersOut:number, vouchersResult:number, expenseItems:ExpenseItem[], incomeAmount:number, incomeCategory:string, incomeConcept:string, incomeReason:string, expenseAmount:number, expenseCategory:string, expensePaymentMethod:"cash"|"bank"|"", expenseConcept:string, expensePaidCash:boolean, refundType:"existing"|"new", refundOriginalId:string, refundAmount:number, refundConcept:string, efectivoReal?:number, efectivoRetiradoObservaciones?:string, createdBy:string, updatedBy?:string, createdAt:number, updatedAt:number, history?:EntryHistoryItem[]}} Entry */
/** @typedef {{id:string, entryId:string, storeId:string, date:string, expectedCash:number, realCash:number, discrepancy:number, observations:string, verifiedBy?:string, verifiedAt?:number, status:"incomplete"|"pending"|"verified"}} CashVerification */
/** @typedef {{id:string, storeId:string, year:number, month:number, status:"incomplete"|"pending"|"verified", verifiedByAdmin?:string, adminVerifiedAt?:number}} MonthlyCashControl */
/** @typedef {{action:"created"|"updated", userId:string, userName:string, timestamp:number, changes?:Record<string,{old:any,new:any}>}} EntryHistoryItem */

const $ = (id) => document.getElementById(id);

const ui = {
  searchInput: $("searchInput"),
  periodSelect: $("periodSelect"),
  storeSelect: $("storeSelect"),
  addEntryBtn: $("addEntryBtn"),
  exportBtn: $("exportBtn"),

  kpiSales: $("kpiSales"),
  kpiExpenses: $("kpiExpenses"),
  kpiMargin: $("kpiMargin"),
  kpiCount: $("kpiCount"),
  kpiSalesDelta: $("kpiSalesDelta"),
  kpiExpensesDelta: $("kpiExpensesDelta"),
  kpiMarginDelta: $("kpiMarginDelta"),
  kpiCountMeta: $("kpiCountMeta"),

  chartSubtitle: $("chartSubtitle"),
  donutTitle: $("donutTitle"),
  donutSubtitle: $("donutSubtitle"),

  entriesTbody: $("entriesTbody"),
  ingresosTbody: $("ingresosTbody"),
  gastosTbody: $("gastosTbody"),
  viewDashboard: $("viewDashboard"),
  viewIngresos: $("viewIngresos"),
  viewGastos: $("viewGastos"),
  viewCierresDiarios: $("viewCierresDiarios"),
  viewPapelera: $("viewPapelera"),
  navIngresos: $("navIngresos"),
  navGastos: $("navGastos"),
  navCierresDiarios: $("navCierresDiarios"),
  navPapelera: $("navPapelera"),
  navMiramiraPedidos: $("navMiramiraPedidos"),
  papeleraTbody: $("papeleraTbody"),
  filterTrashType: $("filterTrashType"),
  filterTrashStore: $("filterTrashStore"),
  filterTrashDays: $("filterTrashDays"),
  emptyTrashBtn: $("emptyTrashBtn"),
  entryHistorySection: $("entryHistorySection"),
  entryHistoryContent: $("entryHistoryContent"),
  addEntryIngresosBtn: $("addEntryIngresosBtn"),
  addEntryGastosBtn: $("addEntryGastosBtn"),
  exportIngresosBtn: $("exportIngresosBtn"),
  exportGastosBtn: $("exportGastosBtn"),
  filterIngresosTienda: $("filterIngresosTienda"),
  filterIngresosPeriodo: $("filterIngresosPeriodo"),
  filterIngresosCustomDates: $("filterIngresosCustomDates"),
  filterIngresosFechaDesde: $("filterIngresosFechaDesde"),
  filterIngresosFechaHasta: $("filterIngresosFechaHasta"),
  filterIngresosTipo: $("filterIngresosTipo"),
  filterIngresosCategoria: $("filterIngresosCategoria"),
  filterIngresosUsuario: $("filterIngresosUsuario"),
  filterGastosTienda: $("filterGastosTienda"),
  filterGastosPeriodo: $("filterGastosPeriodo"),
  filterGastosCustomDates: $("filterGastosCustomDates"),
  filterGastosFechaDesde: $("filterGastosFechaDesde"),
  filterGastosFechaHasta: $("filterGastosFechaHasta"),
  filterGastosTipo: $("filterGastosTipo"),
  filterGastosCategoria: $("filterGastosCategoria"),
  filterGastosUsuario: $("filterGastosUsuario"),
  cierresDiariosTbody: $("cierresDiariosTbody"),
  addCierreDiarioBtn: $("addCierreDiarioBtn"),
  exportCierresDiariosBtn: $("exportCierresDiariosBtn"),
  filterCierresDiariosTienda: $("filterCierresDiariosTienda"),
  filterCierresDiariosPeriodo: $("filterCierresDiariosPeriodo"),
  filterCierresDiariosCustomDates: $("filterCierresDiariosCustomDates"),
  filterCierresDiariosFechaDesde: $("filterCierresDiariosFechaDesde"),
  filterCierresDiariosFechaHasta: $("filterCierresDiariosFechaHasta"),
  filterCierresDiariosUsuario: $("filterCierresDiariosUsuario"),

  entryModal: $("entryModal"),
  closeModalBtn: $("closeModalBtn"),
  cancelBtn: $("cancelBtn"),
  entryForm: $("entryForm"),
  entryId: $("entryId"),
  formStore: $("formStore"),
  formDate: $("formDate"),
  formEntryType: $("formEntryType"),
  formSales: $("formSales"),
  formExpenses: $("formExpenses"),
  formNotes: $("formNotes"),
  formCashInitial: $("formCashInitial"),
  formTpv: $("formTpv"),
  formCashExpenses: $("formCashExpenses"),
  formShopifyCash: $("formShopifyCash"),
  formShopifyTpv: $("formShopifyTpv"),
  formVouchersIn: $("formVouchersIn"),
  formVouchersOut: $("formVouchersOut"),
  formVouchersResult: $("formVouchersResult"),
  addExpenseItemBtn: $("addExpenseItemBtn"),
  expenseItemsContainer: $("expenseItemsContainer"),
  cashCountContainer: $("cashCountContainer"),
  cashCountTotal: $("cashCountTotal"),
  computedCashSales: $("computedCashSales"),
  computedTpvSales: $("computedTpvSales"),
  computedVouchersSales: $("computedVouchersSales"),
  cashDiscrepancy: $("cashDiscrepancy"),
  tpvDiscrepancy: $("tpvDiscrepancy"),
  withdrawAmount: $("withdrawAmount"),
  withdrawHint: $("withdrawHint"),
  sectionDailyClose: $("sectionDailyClose"),
  sectionExpense: $("sectionExpense"),
  sectionIncome: $("sectionIncome"),
  sectionExpenseRefund: $("sectionExpenseRefund"),
  formExpenseAmount: $("formExpenseAmount"),
  formExpenseCategory: $("formExpenseCategory"),
  formExpensePaymentMethod: $("formExpensePaymentMethod"),
  formExpenseConcept: $("formExpenseConcept"),
  formIncomeAmount: $("formIncomeAmount"),
  formIncomeCategory: $("formIncomeCategory"),
  formIncomeConcept: $("formIncomeConcept"),
  formRefundType: $("formRefundType"),
  refundExistingSection: $("refundExistingSection"),
  formRefundOriginalId: $("formRefundOriginalId"),
  formRefundAmount: $("formRefundAmount"),
  formRefundConcept: $("formRefundConcept"),
  formExpenseSplitStores: $("formExpenseSplitStores"),
  expenseSplitContainer: $("expenseSplitContainer"),
  expenseSplitStoreCheckboxes: $("expenseSplitStoreCheckboxes"),
  expenseSplitStoresList: $("expenseSplitStoresList"),
  expenseSplitTotal: $("expenseSplitTotal"),
  expenseSplitError: $("expenseSplitError"),
  formError: $("formError"),
  modalTitle: $("modalTitle"),
  saveBtn: $("saveBtn"),
};

/** -------- Utilities -------- */

// Helper para obtener tiendas disponibles según assignedStores
function getAvailableStores() {
  if (typeof authManager === "undefined" || !authManager) return STORES;
  const assignedStores = authManager.getAssignedStores ? authManager.getAssignedStores() : null;
  if (!assignedStores || (Array.isArray(assignedStores) && assignedStores.length === 0)) {
    return STORES;
  }
  if (Array.isArray(assignedStores)) {
    return STORES.filter((s) => assignedStores.includes(s.id));
  }
  // Compatibilidad hacia atrás: si es string, filtrar por esa tienda
  return STORES.filter((s) => s.id === assignedStores);
}

const euro = new Intl.NumberFormat("es-ES", {
  style: "currency",
  currency: "EUR",
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

function round2(n) {
  return Math.round((Number(n) || 0) * 100) / 100;
}

function todayISO() {
  const d = new Date();
  return new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
    .toISOString()
    .slice(0, 10);
}

function parseISODate(iso) {
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(Date.UTC(y, m - 1, d));
}

function formatShortDate(iso) {
  const dt = parseISODate(iso);
  const dd = String(dt.getUTCDate()).padStart(2, "0");
  const mm = String(dt.getUTCMonth() + 1).padStart(2, "0");
  return `${dd}/${mm}`;
}

function startOfThisMonthUTC() {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1));
}

function startOfThisYearUTC() {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), 0, 1));
}

function addDaysUTC(dt, days) {
  const copy = new Date(dt.getTime());
  copy.setUTCDate(copy.getUTCDate() + days);
  return copy;
}

function isoFromUTCDate(dt) {
  return dt.toISOString().slice(0, 10);
}

function getPeriodRange(periodKey) {
  const now = new Date();
  const end = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59));
  let start;
  if (periodKey === "this_year") start = startOfThisYearUTC();
  else if (periodKey === "last_30") start = addDaysUTC(end, -29);
  else start = startOfThisMonthUTC();
  return { startISO: isoFromUTCDate(start), endISO: isoFromUTCDate(end) };
}

function comparePeriodRange(periodKey, startISO, endISO) {
  const start = parseISODate(startISO);
  const end = parseISODate(endISO);
  const days = Math.round((end.getTime() - start.getTime()) / (24 * 3600 * 1000)) + 1;
  const prevEnd = addDaysUTC(start, -1);
  const prevStart = addDaysUTC(prevEnd, -(days - 1));
  return { prevStartISO: isoFromUTCDate(prevStart), prevEndISO: isoFromUTCDate(prevEnd) };
}

function safeNumberFromInput(value) {
  const raw = String(value ?? "").trim();
  if (!raw) return 0;

  // Acepta:
  // - Formato ES: 1.234,56
  // - Formato EN: 1,234.56
  // - Decimales: 12,50 / 12.50
  // - Miles repetidos: 1.234.567,89 / 1,234,567.89
  let s = raw.replace(/\s+/g, "");
  const hasComma = s.includes(",");
  const hasDot = s.includes(".");

  if (hasComma && hasDot) {
    const lastComma = s.lastIndexOf(",");
    const lastDot = s.lastIndexOf(".");
    if (lastComma > lastDot) {
      // Coma = decimal, punto = miles
      s = s.replaceAll(".", "").replaceAll(",", ".");
    } else {
      // Punto = decimal, coma = miles
      s = s.replaceAll(",", "");
    }
  } else if (hasComma) {
    // Solo coma: asumimos coma decimal (ES)
    s = s.replaceAll(",", ".");
  } else if (hasDot) {
    // Solo punto: si hay más de uno, asumimos miles con decimales en el último punto
    const parts = s.split(".");
    if (parts.length > 2) {
      const dec = parts.pop();
      s = `${parts.join("")}.${dec}`;
    }
  }

  const n = Number(s);
  return Number.isFinite(n) ? n : NaN;
}

function uuid() {
  if (typeof crypto !== "undefined" && crypto.randomUUID) return crypto.randomUUID();
  return `id_${Date.now()}_${Math.random().toString(16).slice(2)}`;
}

function storeName(storeId) {
  return STORES.find((s) => s.id === storeId)?.name ?? storeId;
}

/** -------- Storage -------- */

/** @returns {Entry[]} */
function loadEntries() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed
      .filter((e) => e && typeof e === "object")
      .map((e) => ({
        id: String(e.id ?? uuid()),
        storeId: String(e.storeId ?? ""),
        date: String(e.date ?? ""),
        entryType: ["daily_close", "expense", "income", "expense_refund"].includes(e.entryType)
          ? e.entryType
          : "daily_close",
        sales: Number(e.sales ?? 0),
        expenses: Number(e.expenses ?? 0),
        notes: String(e.notes ?? ""),
        cashInitial: Number(e.cashInitial ?? 0),
        tpv: Number(e.tpv ?? 0),
        cashExpenses: Number(e.cashExpenses ?? 0),
        cashCount: e.cashCount && typeof e.cashCount === "object" ? e.cashCount : {},
        shopifyCash:
          e.shopifyCash === null || e.shopifyCash === undefined || e.shopifyCash === ""
            ? null
            : Number(e.shopifyCash),
        shopifyTpv:
          e.shopifyTpv === null || e.shopifyTpv === undefined || e.shopifyTpv === ""
            ? null
            : Number(e.shopifyTpv),
        vouchersIn: Number(e.vouchersIn ?? 0),
        vouchersOut: Number(e.vouchersOut ?? 0),
        vouchersResult: Number(e.vouchersResult ?? 0),
        expenseItems: Array.isArray(e.expenseItems)
          ? e.expenseItems
              .filter((x) => x && typeof x === "object")
              .map((x) => ({
                id: String(x.id ?? uuid()),
                concept: String(x.concept ?? ""),
                amount: Number(x.amount ?? 0),
                paidWithCash: Boolean(x.paidWithCash ?? true),
              }))
          : [],
        incomeAmount: Number(e.incomeAmount ?? 0),
        incomeCategory: String(e.incomeCategory ?? ""),
        incomeConcept: String(e.incomeConcept ?? e.incomeReason ?? ""),
        incomeReason: String(e.incomeReason ?? ""),
        expenseAmount: Number(e.expenseAmount ?? 0),
        expenseCategory: String(e.expenseCategory ?? ""),
        expensePaymentMethod: String(e.expensePaymentMethod ?? ""),
        expenseConcept: String(e.expenseConcept ?? ""),
        expensePaidCash: Boolean(e.expensePaidCash ?? false),
        refundType: ["existing", "new"].includes(e.refundType) ? e.refundType : "new",
        refundOriginalId: String(e.refundOriginalId ?? ""),
        refundAmount: Number(e.refundAmount ?? 0),
        refundConcept: String(e.refundConcept ?? ""),
        efectivoReal: e.efectivoReal !== undefined && e.efectivoReal !== null ? Number(e.efectivoReal) : undefined,
        efectivoRetiradoObservaciones: String(e.efectivoRetiradoObservaciones ?? ""),
        createdBy: String(e.createdBy ?? ""),
        updatedBy: String(e.updatedBy ?? ""),
        createdAt: Number(e.createdAt ?? Date.now()),
        updatedAt: Number(e.updatedAt ?? Number(e.createdAt ?? Date.now())),
        history: Array.isArray(e.history) ? e.history : [],
      }))
      .filter((e) => e.storeId && e.date);
  } catch {
    return [];
  }
}

/** @param {Entry[]} entries */
function saveEntries(entries) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
}

/** -------- Trash (Papelera) -------- */

/** @returns {Entry[]} */
function loadTrash() {
  const raw = localStorage.getItem(TRASH_STORAGE_KEY);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed
      .filter((e) => e && typeof e === "object")
      .map((e) => ({
        ...e,
        deletedAt: Number(e.deletedAt ?? Date.now()),
        deletedBy: String(e.deletedBy ?? ""),
      }));
  } catch {
    return [];
  }
}

/** @param {Entry[]} trash */
function saveTrash(trash) {
  localStorage.setItem(TRASH_STORAGE_KEY, JSON.stringify(trash));
}

/** Limpia la papelera eliminando registros con más de 30 días */
function cleanTrash() {
  const trash = loadTrash();
  const now = Date.now();
  const retentionMs = TRASH_RETENTION_DAYS * 24 * 60 * 60 * 1000;
  
  const filtered = trash.filter((entry) => {
    const age = now - entry.deletedAt;
    return age < retentionMs;
  });
  
  if (filtered.length !== trash.length) {
    saveTrash(filtered);
  }
}

/** Mueve un registro a la papelera */
function moveToTrash(entryId, deletedBy) {
  const entries = loadEntries();
  const entry = entries.find((e) => e.id === entryId);
  if (!entry) return false;
  
  const trash = loadTrash();
  const entryWithTrashInfo = {
    ...entry,
    deletedAt: Date.now(),
    deletedBy: deletedBy || "",
  };
  trash.push(entryWithTrashInfo);
  saveTrash(trash);
  
  // Eliminar del array principal
  const filtered = entries.filter((e) => e.id !== entryId);
  saveEntries(filtered);
  
  return true;
}

/** Restaura un registro desde la papelera */
function restoreFromTrash(entryId) {
  const trash = loadTrash();
  const entry = trash.find((e) => e.id === entryId);
  if (!entry) return false;
  
  // Remover campos de papelera
  const { deletedAt, deletedBy, ...restoredEntry } = entry;
  
  const entries = loadEntries();
  entries.push(restoredEntry);
  saveEntries(entries);
  
  // Eliminar de la papelera
  const filtered = trash.filter((e) => e.id !== entryId);
  saveTrash(filtered);
  
  return true;
}

/** Elimina permanentemente de la papelera */
function permanentlyDeleteFromTrash(entryId) {
  const trash = loadTrash();
  const filtered = trash.filter((e) => e.id !== entryId);
  saveTrash(filtered);
  return true;
}

function generateRandomCashCount() {
  const cashCount = {};
  for (const denom of DENOMINATIONS) {
    if (Math.random() < 0.6) {
      const maxCount = denom.value >= 50 ? 5 : denom.value >= 10 ? 10 : denom.value >= 1 ? 20 : 50;
      const count = Math.floor(Math.random() * maxCount);
      if (count > 0) cashCount[String(denom.value)] = count;
    }
  }
  return cashCount;
}

function maybeSeedDemoData() {
  const existing = loadEntries();
  if (existing.length > 0) return;

  const now = new Date();
  const end = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
  const start = addDaysUTC(end, -18);

  /** @type {Entry[]} */
  const demo = [];
  const rand = (min, max) => Math.round(min + Math.random() * (max - min));
  for (let d = new Date(start); d <= end; d = addDaysUTC(d, 1)) {
    const iso = isoFromUTCDate(d);
    for (const s of STORES) {
      if (Math.random() < 0.18) continue;
      const base = 800 + Math.random() * 1200;
      const sales = rand(base * 0.6, base * 1.4);
      const expenses = rand(sales * 0.18, sales * 0.45);
      
      const hasCashData = Math.random() < 0.4;
      const cashInitial = hasCashData ? rand(200, 800) : 0;
      const tpv = hasCashData ? rand(sales * 0.3, sales * 0.7) : 0;
      const cashExpenses = hasCashData ? rand(expenses * 0.2, expenses * 0.6) : 0;
      const cashCount = hasCashData ? generateRandomCashCount() : {};
      
      demo.push({
        id: uuid(),
        storeId: s.id,
        date: iso,
        entryType: "daily_close",
        sales,
        expenses,
        notes: Math.random() < 0.12 ? "Promo / evento local" : "",
        cashInitial,
        tpv,
        cashExpenses,
        cashCount,
        shopifyCash: null,
        shopifyTpv: null,
        vouchersIn: 0,
        vouchersOut: 0,
        vouchersResult: 0,
        expenseItems: [],
        incomeAmount: 0,
        incomeCategory: "",
        incomeConcept: "",
        incomeReason: "",
        expenseAmount: 0,
        expenseCategory: "",
        expensePaymentMethod: "",
        expenseConcept: "",
        expensePaidCash: false,
        refundType: "new",
        refundOriginalId: "",
        refundAmount: 0,
        refundConcept: "",
        createdAt: Date.now(),
        updatedAt: Date.now(),
      });
    }
  }
  saveEntries(demo);
}

/** -------- Filtering + Aggregation -------- */

/**
 * @param {Entry[]} entries
 * @param {{storeId:string, startISO:string, endISO:string, search:string}} opts
 */
function filterEntries(entries, opts) {
  const { storeId, startISO, endISO, search } = opts;
  const q = (search || "").trim().toLowerCase();
  return entries
    .filter((e) => (storeId === "ALL" ? true : e.storeId === storeId))
    .filter((e) => e.date >= startISO && e.date <= endISO)
    .filter((e) => (q ? (e.notes || "").toLowerCase().includes(q) : true))
    .sort((a, b) => (a.date === b.date ? b.updatedAt - a.updatedAt : b.date.localeCompare(a.date)));
}

function sumEntries(entries) {
  return entries.reduce(
    (acc, e) => {
      acc.sales += e.sales || 0;
      acc.expenses += e.expenses || 0;
      return acc;
    },
    { sales: 0, expenses: 0 }
  );
}

function percentDelta(current, prev) {
  if (!Number.isFinite(current) || !Number.isFinite(prev)) return null;
  if (prev === 0) return current === 0 ? 0 : null;
  return ((current - prev) / prev) * 100;
}

function deltaBadge(delta) {
  if (delta === null) return `<span class="text-slate-500">Sin comparativa</span>`;
  const up = delta >= 0;
  const cls = up ? "text-emerald-700 bg-emerald-50 ring-emerald-100" : "text-rose-700 bg-rose-50 ring-rose-100";
  const sign = up ? "+" : "";
  const label = `${sign}${delta.toFixed(1)}% vs periodo anterior`;
  return `<span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-semibold ring-1 ${cls}">${label}</span>`;
}

function buildDailySeries(entries, startISO, endISO) {
  const map = new Map();
  for (const e of entries) {
    const prev = map.get(e.date) || { sales: 0, expenses: 0 };
    prev.sales += e.sales || 0;
    prev.expenses += e.expenses || 0;
    map.set(e.date, prev);
  }

  const labels = [];
  const sales = [];
  const expenses = [];

  const start = parseISODate(startISO);
  const end = parseISODate(endISO);
  for (let d = new Date(start); d <= end; d = addDaysUTC(d, 1)) {
    const iso = isoFromUTCDate(d);
    const v = map.get(iso) || { sales: 0, expenses: 0 };
    labels.push(formatShortDate(iso));
    sales.push(v.sales);
    expenses.push(v.expenses);
  }
  return { labels, sales, expenses };
}

function totalsByStore(entries) {
  const totals = new Map(STORES.map((s) => [s.id, { sales: 0, expenses: 0 }]));
  for (const e of entries) {
    const t = totals.get(e.storeId) || { sales: 0, expenses: 0 };
    t.sales += e.sales || 0;
    t.expenses += e.expenses || 0;
    totals.set(e.storeId, t);
  }
  return STORES.map((s) => ({ storeId: s.id, name: s.name, ...totals.get(s.id) }));
}

/** -------- Charts -------- */

let flowChart = null;
let donutChart = null;

function ensureCharts() {
  const ctxFlow = $("flowChart").getContext("2d");
  const ctxDonut = $("donutChart").getContext("2d");

  if (!flowChart) {
    flowChart = new Chart(ctxFlow, {
      type: "bar",
      data: {
        labels: [],
        datasets: [
          {
            label: "Ventas",
            data: [],
            backgroundColor: "rgba(124, 58, 237, 0.65)",
            borderRadius: 8,
            maxBarThickness: 24,
          },
          {
            label: "Gastos",
            data: [],
            backgroundColor: "rgba(244, 63, 94, 0.55)",
            borderRadius: 8,
            maxBarThickness: 24,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "top", align: "end", labels: { boxWidth: 10, usePointStyle: true } },
          tooltip: {
            callbacks: {
              label: (ctx) => `${ctx.dataset.label}: ${euro.format(ctx.raw || 0)}`,
            },
          },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: "#64748b", maxRotation: 0, autoSkip: true } },
          y: {
            grid: { color: "rgba(148,163,184,.25)" },
            ticks: {
              color: "#64748b",
              callback: (v) => euro.format(Number(v || 0)),
            },
          },
        },
      },
    });
  }

  if (!donutChart) {
    donutChart = new Chart(ctxDonut, {
      type: "doughnut",
      data: {
        labels: [],
        datasets: [
          {
            data: [],
            backgroundColor: ["#8b5cf6", "#a78bfa", "#60a5fa", "#34d399", "#fbbf24", "#fb7185"],
            borderWidth: 0,
          },
        ],
      },
      options: {
        cutout: "70%",
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10, usePointStyle: true } },
          tooltip: {
            callbacks: {
              label: (ctx) => `${ctx.label}: ${euro.format(ctx.raw || 0)}`,
            },
          },
        },
      },
    });
  }
}

function updateFlowChart(series) {
  ensureCharts();
  flowChart.data.labels = series.labels;
  flowChart.data.datasets[0].data = series.sales;
  flowChart.data.datasets[1].data = series.expenses;
  flowChart.update();
}

function updateDonutChart(labels, values) {
  ensureCharts();
  donutChart.data.labels = labels;
  donutChart.data.datasets[0].data = values;
  donutChart.update();
}

/** -------- Cash Count -------- */

function renderCashCount(cashCount = {}) {
  ui.cashCountContainer.innerHTML = "";
  for (const denom of DENOMINATIONS) {
    const key = String(denom.value);
    const count = Number(cashCount[key] || 0);
    const div = document.createElement("div");
    div.className = "flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 ring-1 ring-slate-100";
    div.innerHTML = `
      <label class="flex-1">
        <span class="block text-xs font-medium text-slate-700">${denom.label}</span>
        <input
          type="number"
          min="0"
          step="1"
          data-denom="${key}"
          value="${count || ""}"
          placeholder="0"
          class="mt-1 w-full rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-400"
        />
      </label>
    `;
    ui.cashCountContainer.appendChild(div);
  }
  updateCashCountTotal();
  for (const input of ui.cashCountContainer.querySelectorAll("input")) {
    input.addEventListener("input", () => {
      updateCashCountTotal();
      updateVouchersResult();
      updateReconciliation();
      updateDailyCloseTotals();
    });
  }
}

function updateCashCountTotal() {
  let total = 0;
  for (const input of ui.cashCountContainer.querySelectorAll("input")) {
    const denom = Number(input.dataset.denom);
    const count = Number(input.value || 0);
    total += denom * count;
  }
  ui.cashCountTotal.textContent = euro.format(total);
}

function getCashCountFromForm() {
  const cashCount = {};
  for (const input of ui.cashCountContainer.querySelectorAll("input")) {
    const key = input.dataset.denom;
    const count = Number(input.value || 0);
    if (count > 0) cashCount[key] = count;
  }
  return cashCount;
}

function calculateCashTotal(cashCount) {
  let total = 0;
  for (const [key, count] of Object.entries(cashCount)) {
    total += Number(key) * Number(count);
  }
  return total;
}

/** -------- Expense Items -------- */

let expenseItemsDraft = [];

function renderExpenseItems(items = []) {
  expenseItemsDraft = items.length > 0 ? [...items] : [];
  ui.expenseItemsContainer.innerHTML = "";
  if (expenseItemsDraft.length === 0) {
    ui.expenseItemsContainer.innerHTML = `
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-500">
        No hay gastos detallados. Haz clic en "Añadir gasto" para añadir gastos.
      </div>
    `;
    updateExpenseTotals();
    return;
  }
  for (let i = 0; i < expenseItemsDraft.length; i++) {
    const item = expenseItemsDraft[i];
    const row = document.createElement("div");
    row.className = "flex items-center justify-end gap-2";
    row.innerHTML = `
      <button
        type="button"
        data-index="${i}"
        data-action="remove"
        class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-400"
        aria-label="Eliminar"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    `;
    ui.expenseItemsContainer.appendChild(row);
  }
  attachExpenseItemListeners();
  updateExpenseTotals();
}

function attachExpenseItemListeners() {
  if (!ui.expenseItemsContainer) return;
  
  ui.expenseItemsContainer.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-action='remove']");
    if (!btn) return;
    const index = Number(btn.dataset.index);
    if (index >= 0 && index < expenseItemsDraft.length) {
      expenseItemsDraft.splice(index, 1);
      renderExpenseItems(expenseItemsDraft);
    }
  });
}

function updateExpenseTotals() {
  // Como ya no hay items individuales, el total se obtiene directamente del campo formCashExpenses
  const cashExpenses = safeNumberFromInput(ui.formCashExpenses.value);
  const totalExpenses = cashExpenses;
  
  if (ui.formExpenses.tagName === "INPUT") {
    ui.formExpenses.value = totalExpenses > 0 ? String(round2(totalExpenses)) : "";
  } else {
    ui.formExpenses.textContent = totalExpenses > 0 ? euro.format(round2(totalExpenses)) : "—";
  }
  
  updateDailyCloseTotals();
  updateReconciliation();
}

function getExpenseItemsFromForm() {
  // Como ya no hay campos de concepto e importe, retornamos un array vacío
  // El total de gastos se calcula desde otro lugar si es necesario
  return [];
}

/** -------- Daily Close Totals (Auto-calculated) -------- */

function updateVouchersResult() {
  if (ui.formEntryType.value !== "daily_close") return;
  
  const vouchersIn = safeNumberFromInput(ui.formVouchersIn.value);
  const vouchersOut = safeNumberFromInput(ui.formVouchersOut.value);
  const vouchersResult = round2(vouchersIn - vouchersOut);
  
  ui.formVouchersResult.value = String(vouchersResult);
  updateDailyCloseTotals();
  updateReconciliation();
}

function updateDailyCloseTotals() {
  if (ui.formEntryType.value !== "daily_close") return;
  
  const tpv = safeNumberFromInput(ui.formTpv.value);
  const cashInitial = safeNumberFromInput(ui.formCashInitial.value);
  const cashCounted = calculateCashTotal(getCashCountFromForm());
  const cashExpenses = safeNumberFromInput(ui.formCashExpenses.value);
  const vouchersResult = safeNumberFromInput(ui.formVouchersResult.value);
  
  const computedCashSales = round2(cashCounted - cashInitial + cashExpenses);
  const totalSales = round2(tpv + computedCashSales + vouchersResult);
  
  if (ui.formSales.tagName === "INPUT") {
    ui.formSales.value = String(totalSales);
  } else {
    ui.formSales.textContent = euro.format(totalSales);
  }
}

/** -------- Reconciliation -------- */

function updateReconciliation() {
  const cashInitial = safeNumberFromInput(ui.formCashInitial.value);
  const cashCounted = calculateCashTotal(getCashCountFromForm());
  const cashExpenses = safeNumberFromInput(ui.formCashExpenses.value);
  const tpv = safeNumberFromInput(ui.formTpv.value);
  const vouchersResult = safeNumberFromInput(ui.formVouchersResult.value);
  const shopifyCash = safeNumberFromInput(ui.formShopifyCash.value);
  const shopifyTpv = safeNumberFromInput(ui.formShopifyTpv.value);

  const computedCashSales = round2(cashCounted - cashInitial + cashExpenses);
  ui.computedCashSales.textContent = euro.format(computedCashSales);
  
  if (ui.computedTpvSales) {
    ui.computedTpvSales.textContent = euro.format(tpv);
  }
  
  if (ui.computedVouchersSales) {
    ui.computedVouchersSales.textContent = euro.format(vouchersResult);
  }

  const cashDiff = Number.isFinite(shopifyCash) && shopifyCash > 0 ? round2(computedCashSales - shopifyCash) : null;
  const tpvDiff = Number.isFinite(shopifyTpv) && shopifyTpv > 0 ? round2(tpv - shopifyTpv) : null;

  if (cashDiff !== null) {
    const isOk = Math.abs(cashDiff) < 0.01;
    ui.cashDiscrepancy.textContent = isOk ? "✓ OK" : euro.format(cashDiff);
    ui.cashDiscrepancy.className = `mt-1 text-sm font-semibold ${isOk ? "text-emerald-700" : "text-rose-700"}`;
  } else {
    ui.cashDiscrepancy.textContent = "—";
    ui.cashDiscrepancy.className = "mt-1 text-sm font-semibold text-slate-500";
  }

  if (tpvDiff !== null) {
    const isOk = Math.abs(tpvDiff) < 0.01;
    ui.tpvDiscrepancy.textContent = isOk ? "✓ OK" : euro.format(tpvDiff);
    ui.tpvDiscrepancy.className = `mt-1 text-sm font-semibold ${isOk ? "text-emerald-700" : "text-rose-700"}`;
  } else {
    ui.tpvDiscrepancy.textContent = "—";
    ui.tpvDiscrepancy.className = "mt-1 text-sm font-semibold text-slate-500";
  }

  const withdraw = round2(cashCounted + cashExpenses);
  ui.withdrawAmount.textContent = withdraw > 0 ? euro.format(withdraw) : euro.format(0);
  ui.withdrawAmount.className = `mt-1 text-sm font-semibold ${withdraw > 0 ? "text-slate-900" : "text-slate-500"}`;
}

/** -------- Modal -------- */

function showSectionForType(type) {
  ui.sectionDailyClose.classList.add("hidden");
  ui.sectionExpense.classList.add("hidden");
  ui.sectionIncome.classList.add("hidden");
  ui.sectionExpenseRefund.classList.add("hidden");

  if (type === "daily_close") {
    ui.sectionDailyClose.classList.remove("hidden");
  } else if (type === "expense") {
    ui.sectionExpense.classList.remove("hidden");
    // Si la división está activada, inicializar
    if (ui.formExpenseSplitStores && ui.formExpenseSplitStores.checked) {
      renderExpenseSplitStores();
    }
  } else if (type === "income") {
    ui.sectionIncome.classList.remove("hidden");
  } else if (type === "expense_refund") {
    ui.sectionExpenseRefund.classList.remove("hidden");
    updateRefundTypeVisibility();
  }
  
  // Si no es un gasto, ocultar la división entre tiendas
  if (type !== "expense" && ui.expenseSplitContainer) {
    ui.expenseSplitContainer.classList.add("hidden");
    if (ui.formExpenseSplitStores) ui.formExpenseSplitStores.checked = false;
  }
}

function updateRefundTypeVisibility() {
  const isExisting = ui.formRefundType.value === "existing";
  ui.refundExistingSection.style.display = isExisting ? "block" : "none";
}

function openModal(mode, entry, allowedTypes = null) {
  ui.formError.classList.add("hidden");
  ui.formError.textContent = "";

  ui.entryId.value = entry?.id || "";
  const entryType = entry?.entryType || (allowedTypes && allowedTypes.length > 0 ? allowedTypes[0] : "daily_close");
  ui.formEntryType.value = entryType;

  const typeLabels = {
    daily_close: "Cierre diario",
    expense: "Gasto",
    income: "Ingreso",
    expense_refund: "Devolución de gasto",
  };
  ui.modalTitle.textContent = mode === "edit" ? `Editar ${typeLabels[entryType]}` : `Añadir ${typeLabels[entryType]}`;
  ui.saveBtn.textContent = mode === "edit" ? "Guardar cambios" : "Guardar";

  // Poblar el select de tiendas con las tiendas disponibles
  const availableStores = getAvailableStores();
  if (ui.formStore) {
    const currentValue = ui.formStore.value;
    setSelectOptions(ui.formStore, availableStores, false);
    // Establecer el valor: primero intentar el del entry, luego el del selector principal, luego la primera disponible
    const defaultStoreId = entry?.storeId || (ui.storeSelect.value === "ALL" ? (availableStores.length > 0 ? availableStores[0].id : null) : ui.storeSelect.value);
    if (defaultStoreId && availableStores.find(s => s.id === defaultStoreId)) {
      ui.formStore.value = defaultStoreId;
    } else if (currentValue && availableStores.find(s => s.id === currentValue)) {
      ui.formStore.value = currentValue;
    } else if (availableStores.length > 0) {
      ui.formStore.value = availableStores[0].id;
    }
  }

  ui.formDate.value = entry?.date || todayISO();

  ui.formNotes.value = entry?.notes || "";

  if (entryType === "daily_close") {
    ui.formCashInitial.value = entry?.cashInitial != null ? String(entry.cashInitial) : "";
    ui.formTpv.value = entry?.tpv != null ? String(entry.tpv) : "";
    // formCashExpenses ahora es editable directamente
    ui.formCashExpenses.value = entry?.cashExpenses != null ? String(entry.cashExpenses) : "";
    ui.formShopifyCash.value = entry?.shopifyCash != null ? String(entry.shopifyCash) : "";
    ui.formShopifyTpv.value = entry?.shopifyTpv != null ? String(entry.shopifyTpv) : "";
    ui.formVouchersIn.value = entry?.vouchersIn != null ? String(entry.vouchersIn) : "";
    ui.formVouchersOut.value = entry?.vouchersOut != null ? String(entry.vouchersOut) : "";
    if (ui.expenseItemsContainer) {
    renderExpenseItems(entry?.expenseItems || []);
    }
    renderCashCount(entry?.cashCount || {});
    updateVouchersResult();
    updateDailyCloseTotals();
    updateReconciliation();
    updateExpenseTotals();
  } else if (entryType === "expense") {
    ui.formExpenseAmount.value = entry?.expenseAmount != null ? String(entry.expenseAmount) : "";
    ui.formExpenseCategory.value = entry?.expenseCategory || "";
    ui.formExpensePaymentMethod.value = entry?.expensePaymentMethod || "";
    ui.formExpenseConcept.value = entry?.expenseConcept || "";
    // Resetear división entre tiendas (no se puede editar un gasto dividido)
    if (ui.formExpenseSplitStores) ui.formExpenseSplitStores.checked = false;
    if (ui.expenseSplitContainer) ui.expenseSplitContainer.classList.add("hidden");
  } else if (entryType === "income") {
    ui.formIncomeAmount.value = entry?.incomeAmount != null ? String(entry.incomeAmount) : "";
    ui.formIncomeCategory.value = entry?.incomeCategory || "";
    ui.formIncomeConcept.value = entry?.incomeConcept || entry?.incomeReason || "";
  } else if (entryType === "expense_refund") {
    ui.formRefundType.value = entry?.refundType || "new";
    ui.formRefundOriginalId.value = entry?.refundOriginalId || "";
    ui.formRefundAmount.value = entry?.refundAmount != null ? String(entry.refundAmount) : "";
    ui.formRefundConcept.value = entry?.refundConcept || "";
    updateRefundTypeVisibility();
  }

  showSectionForType(entryType);

  // Mostrar historial si estamos editando
  if (mode === "edit" && entry) {
    renderEntryHistory(entry);
  } else {
    if (ui.entryHistorySection) ui.entryHistorySection.classList.add("hidden");
  }

  ui.entryModal.classList.remove("hidden");
  ui.entryModal.classList.add("flex");
  ui.formEntryType.focus();

  // Si estamos creando, aplicar restricción por tipos permitidos
  applyCreateTypeRestrictions(mode, allowedTypes);
}

function applyCreateTypeRestrictions(mode, contextAllowedTypes = null) {
  const select = ui.formEntryType;
  if (!select) return;

  let allowed = [];
  
  // Primero aplicar restricciones del contexto (si vienen desde Ingresos o Gastos)
  if (contextAllowedTypes && contextAllowedTypes.length > 0) {
    allowed = contextAllowedTypes;
  } else if (mode === "create" && typeof roleManager !== "undefined" && roleManager) {
    // Si no hay restricción de contexto, aplicar restricciones de roles
    allowed = typeof roleManager.getAllowedCreateTypes === "function" ? roleManager.getAllowedCreateTypes() : [];
  } else {
    // Si no hay restricciones, permitir todos
    allowed = ["daily_close", "expense", "income", "expense_refund"];
  }

  const options = Array.from(select.options || []);
  for (const opt of options) {
    if (allowed.length > 0) {
      opt.disabled = !allowed.includes(opt.value);
      opt.hidden = opt.disabled;
    } else {
      opt.disabled = false;
      opt.hidden = false;
    }
  }

  // Si el seleccionado no está permitido, mover al primero permitido
  if (allowed.length > 0 && !allowed.includes(select.value)) {
    select.value = allowed[0];
    showSectionForType(select.value);
  }

  // Si solo hay un tipo permitido, ocultar el selector de tipo
  if (mode === "add" && allowed.length === 1) {
    const typeSelectLabel = select.closest("label");
    if (typeSelectLabel) {
      typeSelectLabel.style.display = "none";
    }
  } else {
    const typeSelectLabel = select.closest("label");
    if (typeSelectLabel) {
      typeSelectLabel.style.display = "";
    }
  }
}

function closeModal() {
  ui.entryModal.classList.add("hidden");
  ui.entryModal.classList.remove("flex");
}

function closeTypeSelectionModal() {
  const modal = document.getElementById("typeSelectionModal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

function showTypeSelectionModal(allowedTypes, callback) {
  // Si solo hay un tipo permitido, abrir directamente el formulario
  if (allowedTypes && allowedTypes.length === 1) {
    callback(allowedTypes[0]);
    return;
  }

  // Crear o obtener el modal de selección de tipo
  let modal = document.getElementById("typeSelectionModal");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "typeSelectionModal";
    modal.className = "fixed inset-0 hidden items-center justify-center bg-slate-900/40 p-4 z-50";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    document.body.appendChild(modal);
  }

  const typeLabels = {
    daily_close: "Cierre diario",
    expense: "Gasto",
    income: "Ingreso",
    expense_refund: "Devolución de gasto",
  };

  const typeIcons = {
    daily_close: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`,
    expense: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`,
    income: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`,
    expense_refund: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`,
  };

  // Filtrar solo los tipos permitidos
  const availableTypes = allowedTypes && allowedTypes.length > 0 
    ? allowedTypes 
    : ["daily_close", "expense", "income", "expense_refund"];

  // Crear el contenido del modal
  modal.innerHTML = `
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-soft">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Seleccionar tipo de registro</h2>
          <p class="text-sm text-slate-500 mt-1">Elige el tipo de registro que deseas añadir</p>
        </div>
        <button
          id="closeTypeSelectionModalBtn"
          class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
          aria-label="Cerrar"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </button>
      </div>
      <div class="grid grid-cols-1 gap-3">
        ${availableTypes.map(type => `
          <button
            type="button"
            data-type="${type}"
            class="type-selection-btn flex items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-4 text-left transition-all hover:border-brand-500 hover:bg-brand-50 hover:shadow-sm"
          >
            <div class="flex-shrink-0 text-brand-600">
              ${typeIcons[type] || ''}
            </div>
            <div class="flex-1">
              <div class="font-semibold text-slate-900">${typeLabels[type]}</div>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-400">
              <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        `).join('')}
      </div>
    </div>
  `;

  // Event listeners para cerrar el modal
  const closeBtn = modal.querySelector("#closeTypeSelectionModalBtn");
  if (closeBtn) {
    closeBtn.addEventListener("click", closeTypeSelectionModal);
  }

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      closeTypeSelectionModal();
    }
  });

  // Event listeners para los botones de tipo
  const typeButtons = modal.querySelectorAll(".type-selection-btn");
  typeButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const selectedType = btn.dataset.type;
      closeTypeSelectionModal();
      callback(selectedType);
    });
  });

  // Cerrar con Escape
  const handleEscape = (e) => {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) {
      closeTypeSelectionModal();
      document.removeEventListener("keydown", handleEscape);
    }
  };
  document.addEventListener("keydown", handleEscape);

  // Mostrar el modal
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function openModalWithTypeSelection(allowedTypes = null) {
  // Obtener tipos permitidos
  let types = allowedTypes;
  
  if (!types || types.length === 0) {
    if (typeof roleManager !== "undefined" && roleManager) {
      types = typeof roleManager.getAllowedCreateTypes === "function" 
        ? roleManager.getAllowedCreateTypes() 
        : ["daily_close", "expense", "income", "expense_refund"];
    } else {
      types = ["daily_close", "expense", "income", "expense_refund"];
    }
  }

  // Mostrar modal de selección o abrir directamente
  showTypeSelectionModal(types, (selectedType) => {
    openModal("create", null, [selectedType]);
  });
}

function showFormError(msg) {
  ui.formError.textContent = msg;
  ui.formError.classList.remove("hidden");
}

/** -------- Expense Split Functions -------- */

function renderExpenseSplitStores() {
  if (!ui.expenseSplitStoreCheckboxes) {
    console.error("expenseSplitStoreCheckboxes no encontrado");
    return;
  }
  
  const totalAmount = safeNumberFromInput(ui.formExpenseAmount?.value || "0");
  const assignedStores = typeof authManager !== "undefined" && authManager && typeof authManager.getAssignedStores === "function" ? authManager.getAssignedStores() : null;
  const availableStores = assignedStores && Array.isArray(assignedStores) && assignedStores.length > 0 
    ? STORES.filter((s) => assignedStores.includes(s.id))
    : STORES;
  
  if (availableStores.length === 0) {
    ui.expenseSplitStoreCheckboxes.innerHTML = '<div class="text-xs text-slate-500">No hay tiendas disponibles</div>';
    return;
  }
  
  // Renderizar checkboxes de selección de tiendas
  ui.expenseSplitStoreCheckboxes.innerHTML = "";
  availableStores.forEach((store) => {
    const div = document.createElement("div");
    div.className = "flex items-center gap-2";
    const checkboxId = `expenseSplitCheck_${store.id}`;
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.id = checkboxId;
    checkbox.dataset.storeId = store.id;
    checkbox.className = "h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500 cursor-pointer";
    checkbox.checked = true;
    
    const label = document.createElement("label");
    label.htmlFor = checkboxId;
    label.className = "text-xs font-medium text-slate-700 cursor-pointer";
    label.textContent = store.name;
    
    div.appendChild(checkbox);
    div.appendChild(label);
    ui.expenseSplitStoreCheckboxes.appendChild(div);
  });
  
  // Renderizar inputs de cantidad solo para tiendas seleccionadas
  updateExpenseSplitInputs();
}

function updateExpenseSplitInputs() {
  if (!ui.expenseSplitStoresList || !ui.expenseSplitStoreCheckboxes) return;
  
  const totalAmount = safeNumberFromInput(ui.formExpenseAmount.value);
  const availableStores = getAvailableStores();
  
  // Obtener tiendas seleccionadas
  const selectedStores = [];
  const checkboxes = ui.expenseSplitStoreCheckboxes.querySelectorAll("input[type='checkbox']");
  checkboxes.forEach((cb) => {
    if (cb.checked) {
      const storeId = cb.dataset.storeId;
      const store = availableStores.find((s) => s.id === storeId);
      if (store) selectedStores.push(store);
    }
  });
  
  if (selectedStores.length === 0) {
    ui.expenseSplitStoresList.innerHTML = '<div class="text-xs text-slate-500 text-center py-2">Selecciona al menos una tienda</div>';
    if (ui.expenseSplitTotal) ui.expenseSplitTotal.textContent = euro.format(0);
    return;
  }
  
  ui.expenseSplitStoresList.innerHTML = "";
  
  // Calcular división por partes iguales entre las tiendas seleccionadas
  const equalAmount = selectedStores.length > 0 ? round2(totalAmount / selectedStores.length) : 0;
  const remainder = round2(totalAmount - (equalAmount * selectedStores.length));
  
  selectedStores.forEach((store, index) => {
    const div = document.createElement("div");
    div.className = "flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-2 ring-1 ring-slate-100";
    // El último recibe el resto para que la suma sea exacta
    const amount = index === selectedStores.length - 1 ? round2(equalAmount + remainder) : equalAmount;
    div.innerHTML = `
      <label class="flex-1">
        <span class="block text-xs font-medium text-slate-700">${store.name}</span>
        <input
          type="number"
          data-store-id="${store.id}"
          data-store-name="${store.name}"
          inputmode="decimal"
          step="0.01"
          min="0"
          value="${amount}"
          class="mt-1 w-full rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-400"
        />
      </label>
    `;
    ui.expenseSplitStoresList.appendChild(div);
  });
  
  updateExpenseSplitTotal();
}

function updateExpenseSplitTotal() {
  if (!ui.expenseSplitTotal || !ui.expenseSplitStoresList) return;
  
  let total = 0;
  const inputs = ui.expenseSplitStoresList.querySelectorAll("input[type='number']");
  inputs.forEach((input) => {
    const val = safeNumberFromInput(input.value);
    total += val;
  });
  
  ui.expenseSplitTotal.textContent = euro.format(round2(total));
  
  // Validar que la suma sea igual al total
  const totalAmount = safeNumberFromInput(ui.formExpenseAmount.value);
  const isValid = Math.abs(total - totalAmount) < 0.01; // Tolerancia de 1 céntimo
  
  if (ui.expenseSplitError) {
    if (isValid) {
      ui.expenseSplitError.classList.add("hidden");
    } else {
      ui.expenseSplitError.classList.remove("hidden");
    }
  }
  
  // Cambiar color del total según validez
  if (ui.expenseSplitTotal) {
    if (isValid) {
      ui.expenseSplitTotal.classList.remove("text-rose-700");
      ui.expenseSplitTotal.classList.add("text-brand-700");
    } else {
      ui.expenseSplitTotal.classList.remove("text-brand-700");
      ui.expenseSplitTotal.classList.add("text-rose-700");
    }
  }
}

function getExpenseSplitStores() {
  if (!ui.expenseSplitStoresList) return [];
  
  const splits = [];
  const inputs = ui.expenseSplitStoresList.querySelectorAll("input[type='number']");
  inputs.forEach((input) => {
    const storeId = input.dataset.storeId;
    const storeName = input.dataset.storeName;
    const amount = safeNumberFromInput(input.value);
    if (storeId && amount > 0) {
      splits.push({ storeId, storeName, amount });
    }
  });
  
  return splits;
}

/** -------- Rendering -------- */

function setSelectOptions(select, options, includeAll) {
  select.innerHTML = "";
  if (includeAll) {
    const opt = document.createElement("option");
    opt.value = "ALL";
    opt.textContent = "Todas (empresa)";
    select.appendChild(opt);
  }
  for (const s of options) {
    const opt = document.createElement("option");
    opt.value = s.id;
    opt.textContent = s.name;
    select.appendChild(opt);
  }
}

function renderTable(entries) {
  ui.entriesTbody.innerHTML = "";
  const rows = entries.slice(0, 12);
  if (rows.length === 0) {
    ui.entriesTbody.innerHTML = `
      <tr>
        <td class="px-3 py-6 text-center text-slate-500" colspan="8">
          No hay registros para este filtro. Añade uno con "Añadir registro".
        </td>
      </tr>
    `;
    return;
  }

  const typeLabels = {
    daily_close: { label: "Cierre diario", color: "bg-brand-50 text-brand-700 ring-brand-100" },
    expense: { label: "Gasto", color: "bg-rose-50 text-rose-700 ring-rose-100" },
    income: { label: "Ingreso", color: "bg-emerald-50 text-emerald-700 ring-emerald-100" },
    expense_refund: { label: "Devolución", color: "bg-amber-50 text-amber-700 ring-amber-100" },
  };

  for (const e of rows) {
    const entryType = e.entryType || "daily_close";
    const typeInfo = typeLabels[entryType] || typeLabels.daily_close;
    const margin = (e.sales || 0) - (e.expenses || 0);
    const hasCashData = entryType === "daily_close" && ((e.cashInitial || 0) > 0 || (e.tpv || 0) > 0 || Object.keys(e.cashCount || {}).length > 0);
    const cashCounted = calculateCashTotal(e.cashCount || {});

    let detailText = e.notes || "";
    if (entryType === "expense" && (e.expenseCategory || e.expenseConcept)) {
      const cat = e.expenseCategory ? `[${e.expenseCategory}] ` : "";
      const pm = e.expensePaymentMethod ? `(${e.expensePaymentMethod}) ` : "";
      detailText = `${cat}${pm}${e.expenseConcept || ""}`.trim();
    } else if (entryType === "income" && (e.incomeCategory || e.incomeConcept || e.incomeReason)) {
      const cat = e.incomeCategory ? `[${e.incomeCategory}] ` : "";
      detailText = `${cat}${e.incomeConcept || e.incomeReason || ""}`.trim();
    } else if (entryType === "expense_refund" && e.refundConcept) {
      detailText = e.refundConcept;
    }

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
      <td class="whitespace-nowrap px-3 py-3">
        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${typeInfo.color}">
          ${typeInfo.label}
        </span>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900">${euro.format(e.sales || 0)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900">${euro.format(e.expenses || 0)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-medium ${margin >= 0 ? "text-emerald-700" : "text-rose-700"}">${euro.format(margin)}</td>
      <td class="max-w-[360px] px-3 py-3 text-slate-600">
        <div class="flex items-center gap-2">
          <div class="truncate flex-1">${escapeHtml(detailText)}</div>
          ${hasCashData ? `<span class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-100" title="Cierre de caja: Efectivo inicial ${euro.format(e.cashInitial || 0)}, TPV ${euro.format(e.tpv || 0)}, Efectivo contado ${euro.format(cashCounted)}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 4H3a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Caja
          </span>` : ""}
        </div>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-right">
        <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
          Editar
        </button>
        <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
          Borrar
        </button>
      </td>
    `;
    ui.entriesTbody.appendChild(tr);
  }
}

function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function formatDateTime(timestamp) {
  const date = new Date(timestamp);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  return `${day}/${month}/${year} ${hours}:${minutes}`;
}

function renderEntryHistory(entry) {
  try {
    if (!ui.entryHistorySection || !ui.entryHistoryContent) return;
    
    if (!entry) {
      ui.entryHistorySection.classList.add("hidden");
      return;
    }
    
    // Si no hay historial, generar uno automáticamente basado en createdAt/createdBy y updatedAt/updatedBy
    let history = entry.history || [];
  
  // Si no hay historial pero hay datos de creación, generar entrada de creación
  if (history.length === 0 && entry.createdAt) {
    const createdBy = entry.createdBy || "Sistema";
    // Intentar obtener el nombre del usuario desde authManager si es posible
    let userName = createdBy || "Usuario desconocido";
    try {
      if (typeof authManager !== "undefined" && authManager && typeof authManager.isAuthenticated === "function" && authManager.isAuthenticated()) {
        if (typeof userManager !== "undefined" && userManager && typeof userManager.getUsers === "function") {
          const users = userManager.getUsers();
          const user = users.find(u => u.id === createdBy || u.username === createdBy);
          if (user) {
            userName = user.name || user.username || createdBy || "Usuario desconocido";
          }
        }
      }
    } catch (e) {
      // Si hay error, usar el valor por defecto
      console.warn("Error al obtener nombre de usuario:", e);
    }
    
    history.push({
      action: "created",
      userId: createdBy,
      userName: userName,
      timestamp: entry.createdAt
    });
    
    // Si hay actualización y es diferente de la creación, agregar entrada de modificación
    if (entry.updatedAt && entry.updatedAt !== entry.createdAt && entry.updatedBy) {
      let updatedUserName = entry.updatedBy || "Usuario desconocido";
      try {
        if (typeof authManager !== "undefined" && authManager && typeof authManager.isAuthenticated === "function" && authManager.isAuthenticated()) {
          if (typeof userManager !== "undefined" && userManager && typeof userManager.getUsers === "function") {
            const users = userManager.getUsers();
            const user = users.find(u => u.id === entry.updatedBy || u.username === entry.updatedBy);
            if (user) {
              updatedUserName = user.name || user.username || entry.updatedBy || "Usuario desconocido";
            }
          }
        }
      } catch (e) {
        // Si hay error, usar el valor por defecto
        console.warn("Error al obtener nombre de usuario actualizado:", e);
      }
      
      history.push({
        action: "updated",
        userId: entry.updatedBy,
        userName: updatedUserName,
        timestamp: entry.updatedAt
      });
    }
  }
  
  if (history.length === 0) {
    ui.entryHistorySection.classList.add("hidden");
    return;
  }
  
  ui.entryHistorySection.classList.remove("hidden");
  
  let html = "";
  const sortedHistory = [...history].sort((a, b) => b.timestamp - a.timestamp);
  
  sortedHistory.forEach((item) => {
    const actionLabel = item.action === "created" ? "Creado" : "Modificado";
    const actionColor = item.action === "created" ? "text-emerald-700 bg-emerald-50" : "text-blue-700 bg-blue-50";
    
    html += `
      <div class="rounded-lg border border-slate-200 bg-white p-3 ring-1 ring-slate-100">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ring-1 ${actionColor}">
                ${actionLabel}
              </span>
              <span class="text-xs font-medium text-slate-900">${escapeHtml(item.userName || "Usuario desconocido")}</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">${formatDateTime(item.timestamp)}</div>
            ${item.changes && Object.keys(item.changes).length > 0 ? `
              <div class="mt-2 space-y-1">
                ${Object.entries(item.changes).map(([field, change]) => {
                  const fieldLabels = {
                    storeId: "Tienda",
                    date: "Fecha",
                    entryType: "Tipo",
                    sales: "Ventas",
                    expenses: "Gastos",
                    notes: "Notas",
                    cashInitial: "Efectivo inicial",
                    tpv: "TPV",
                    expenseAmount: "Importe gasto",
                    expenseCategory: "Categoría gasto",
                    expenseConcept: "Concepto gasto",
                    incomeAmount: "Importe ingreso",
                    incomeCategory: "Categoría ingreso",
                    incomeConcept: "Concepto ingreso",
                    refundAmount: "Importe devolución",
                    refundConcept: "Concepto devolución"
                  };
                  const label = fieldLabels[field] || field;
                  const oldVal = change.old !== null && change.old !== undefined ? String(change.old) : "—";
                  const newVal = change.new !== null && change.new !== undefined ? String(change.new) : "—";
                  return `
                    <div class="text-xs text-slate-600">
                      <span class="font-medium">${label}:</span>
                      <span class="text-rose-600 line-through">${escapeHtml(oldVal)}</span>
                      <span class="mx-1">→</span>
                      <span class="text-emerald-600 font-medium">${escapeHtml(newVal)}</span>
                    </div>
                  `;
                }).join("")}
              </div>
            ` : ""}
          </div>
        </div>
      </div>
    `;
  });
  
  ui.entryHistoryContent.innerHTML = html;
  } catch (error) {
    console.error("Error al renderizar historial:", error);
    // Si hay error, ocultar la sección de historial para que no bloquee la edición
    if (ui.entryHistorySection) {
      ui.entryHistorySection.classList.add("hidden");
    }
  }
}

function populateTrashFilters() {
  // Llenar selector de tiendas
  if (ui.filterTrashStore) {
    const currentValue = ui.filterTrashStore.value;
    ui.filterTrashStore.innerHTML = '<option value="ALL">Todas</option>';
    STORES.forEach(store => {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterTrashStore.appendChild(opt);
    });
    if (currentValue) ui.filterTrashStore.value = currentValue;
  }
}

function renderPapelera() {
  if (!ui.papeleraTbody) return;
  
  cleanTrash(); // Limpiar registros antiguos antes de mostrar
  
  let trash = loadTrash();
  
  // Filtros
  if (ui.filterTrashType && ui.filterTrashType.value) {
    trash = trash.filter(e => e.entryType === ui.filterTrashType.value);
  }
  
  if (ui.filterTrashStore && ui.filterTrashStore.value !== "ALL") {
    trash = trash.filter(e => e.storeId === ui.filterTrashStore.value);
  }
  
  if (ui.filterTrashDays && ui.filterTrashDays.value) {
    const now = Date.now();
    const retentionMs = TRASH_RETENTION_DAYS * 24 * 60 * 60 * 1000;
    const [minDays, maxDays] = ui.filterTrashDays.value.split("-").map(Number);
    trash = trash.filter(e => {
      const age = now - e.deletedAt;
      const daysRemaining = TRASH_RETENTION_DAYS - Math.floor(age / (24 * 60 * 60 * 1000));
      return daysRemaining >= minDays && daysRemaining <= (maxDays || TRASH_RETENTION_DAYS);
    });
  }
  
  // Ordenar por fecha de eliminación (más recientes primero)
  trash.sort((a, b) => b.deletedAt - a.deletedAt);
  
  if (trash.length === 0) {
    ui.papeleraTbody.innerHTML = `
      <tr>
        <td class="px-3 py-6 text-center text-slate-500" colspan="8">
          La papelera está vacía.
        </td>
      </tr>
    `;
    return;
  }
  
  ui.papeleraTbody.innerHTML = "";
  
  const typeLabels = {
    daily_close: { label: "Cierre diario", color: "bg-brand-50 text-brand-700 ring-brand-100" },
    expense: { label: "Gasto", color: "bg-rose-50 text-rose-700 ring-rose-100" },
    income: { label: "Ingreso", color: "bg-emerald-50 text-emerald-700 ring-emerald-100" },
    expense_refund: { label: "Devolución", color: "bg-amber-50 text-amber-700 ring-amber-100" },
  };
  
  trash.forEach(entry => {
    const typeInfo = typeLabels[entry.entryType] || typeLabels.daily_close;
    const importe = entry.entryType === "daily_close" 
      ? (entry.sales || 0) 
      : entry.entryType === "expense" 
        ? (entry.expenseAmount || 0)
        : entry.entryType === "income"
          ? (entry.incomeAmount || 0)
          : (entry.refundAmount || 0);
    
    const now = Date.now();
    const age = now - entry.deletedAt;
    const daysRemaining = Math.max(0, Math.floor(TRASH_RETENTION_DAYS - age / (24 * 60 * 60 * 1000)));
    
    // Obtener nombre del usuario que eliminó
    let deletedByName = "—";
    if (entry.deletedBy && typeof authManager !== "undefined" && authManager) {
      const users = authManager.getAllUsers();
      const user = users.find(u => u.id === entry.deletedBy);
      if (user) {
        deletedByName = user.name || user.username;
      }
    }
    
    const tr = document.createElement("tr");
    tr.className = "hover:bg-slate-50";
    tr.innerHTML = `
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${entry.date}</td>
      <td class="whitespace-nowrap px-3 py-3">
        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${typeInfo.color}">
          ${typeInfo.label}
        </span>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(entry.storeId)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900 text-right">${euro.format(importe)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${escapeHtml(deletedByName)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${formatDateTime(entry.deletedAt)}</td>
      <td class="whitespace-nowrap px-3 py-3">
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${daysRemaining <= 7 ? "bg-rose-100 text-rose-700" : daysRemaining <= 15 ? "bg-amber-100 text-amber-700" : "bg-emerald-100 text-emerald-700"}">
          ${Math.floor(daysRemaining)} días
        </span>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-right">
        <span class="inline-flex items-center gap-1 flex-nowrap">
        <button
          data-action="restore"
          data-id="${entry.id}"
          class="rounded-lg px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 ring-1 ring-transparent hover:ring-emerald-100"
        >
          Restaurar
        </button>
        <button
          data-action="delete-permanent"
          data-id="${entry.id}"
          class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100"
        >
          Eliminar
        </button>
        </span>
      </td>
    `;
    ui.papeleraTbody.appendChild(tr);
  });
}

function renderKPIs(curSum, prevSum, count, startISO, endISO) {
  const curMargin = curSum.sales - curSum.expenses;
  const prevMargin = prevSum.sales - prevSum.expenses;

  ui.kpiSales.textContent = euro.format(curSum.sales);
  ui.kpiExpenses.textContent = euro.format(curSum.expenses);
  ui.kpiMargin.textContent = euro.format(curMargin);
  ui.kpiCount.textContent = String(count);

  ui.kpiSalesDelta.innerHTML = deltaBadge(percentDelta(curSum.sales, prevSum.sales));
  ui.kpiExpensesDelta.innerHTML = deltaBadge(percentDelta(curSum.expenses, prevSum.expenses));
  ui.kpiMarginDelta.innerHTML = deltaBadge(percentDelta(curMargin, prevMargin));

  ui.kpiCountMeta.textContent = `${startISO} → ${endISO}`;
}

function renderCharts(filteredEntries, storeId, startISO, endISO) {
  ui.chartSubtitle.textContent = storeId === "ALL" ? "Empresa (suma de 4 tiendas)" : storeName(storeId);

  const series = buildDailySeries(filteredEntries, startISO, endISO);
  updateFlowChart(series);

  if (storeId === "ALL") {
    const byStore = totalsByStore(filteredEntries);
    const labels = byStore.map((x) => x.name.replace("Miramira - ", ""));
    const values = byStore.map((x) => Math.round(x.sales));
    ui.donutTitle.textContent = "Ventas por tienda";
    ui.donutSubtitle.textContent = "Distribución dentro del periodo";
    updateDonutChart(labels, values);
  } else {
    const sum = sumEntries(filteredEntries);
    const margin = Math.max(0, sum.sales - sum.expenses);
    ui.donutTitle.textContent = "Gastos vs margen";
    ui.donutSubtitle.textContent = "Dentro del periodo";
    updateDonutChart(["Gastos", "Margen"], [Math.round(sum.expenses), Math.round(margin)]);
  }
}

/** -------- Vistas: Ingresos y Gastos -------- */

function showView(viewName) {
  // Ocultar todas las vistas
  if (ui.viewDashboard) ui.viewDashboard.style.display = viewName === "dashboard" ? "block" : "none";
  if (ui.viewIngresos) ui.viewIngresos.classList.toggle("hidden", viewName !== "ingresos");
  if (ui.viewGastos) ui.viewGastos.classList.toggle("hidden", viewName !== "gastos");
  if (ui.viewCierresDiarios) ui.viewCierresDiarios.classList.toggle("hidden", viewName !== "cierresDiarios");
  if (ui.viewPapelera) ui.viewPapelera.classList.toggle("hidden", viewName !== "papelera");
  
  // Vista de pedidos
  const viewMiramiraPedidos = document.getElementById('viewMiramiraPedidos');
  if (viewMiramiraPedidos) {
    viewMiramiraPedidos.classList.toggle("hidden", viewName !== "miramiraPedidos");
    if (viewName === "miramiraPedidos" && typeof orderManager !== "undefined" && orderManager) {
      if (typeof orderManager.populateStoreFilter === "function") {
        orderManager.populateStoreFilter();
      }
      if (typeof orderManager.renderOrdersSummary === "function") {
        orderManager.renderOrdersSummary();
      }
      orderManager.renderOrdersTable();
      orderManager.initializeEventListeners();
    }
  } else if (viewName === "miramiraPedidos" && typeof orderManager !== "undefined" && orderManager) {
    // Si la vista no existe, crearla
    if (typeof orderManager.createOrdersView === "function") {
      orderManager.createOrdersView();
      // Asegurar que la vista se muestre después de crearla
      const newView = document.getElementById('viewMiramiraPedidos');
      if (newView) {
        newView.classList.remove("hidden");
      }
    }
  }

  // Actualizar estilos del menú
  const navLinks = document.querySelectorAll("nav a");
  navLinks.forEach((link) => {
    link.classList.remove("bg-brand-50", "text-brand-800", "ring-brand-100");
    link.classList.add("text-slate-700");
    const icon = link.querySelector("span");
    if (icon) icon.classList.remove("text-brand-700");
    if (icon) icon.classList.add("text-slate-500");
  });

  if (viewName === "dashboard") {
    const dashboardLink = navLinks[0];
    if (dashboardLink) {
      dashboardLink.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
      dashboardLink.classList.remove("text-slate-700");
      const icon = dashboardLink.querySelector("span");
      if (icon) icon.classList.add("text-brand-700");
      if (icon) icon.classList.remove("text-slate-500");
    }
  } else if (viewName === "ingresos" && ui.navIngresos) {
    ui.navIngresos.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
    ui.navIngresos.classList.remove("text-slate-700");
    const icon = ui.navIngresos.querySelector("span");
    if (icon) icon.classList.add("text-brand-700");
    if (icon) icon.classList.remove("text-slate-500");
  } else if (viewName === "gastos" && ui.navGastos) {
    ui.navGastos.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
    ui.navGastos.classList.remove("text-slate-700");
    const icon = ui.navGastos.querySelector("span");
    if (icon) icon.classList.add("text-brand-700");
    if (icon) icon.classList.remove("text-slate-500");
  } else if (viewName === "cierresDiarios" && ui.navCierresDiarios) {
    ui.navCierresDiarios.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
    ui.navCierresDiarios.classList.remove("text-slate-700");
    const icon = ui.navCierresDiarios.querySelector("span");
    if (icon) icon.classList.add("text-brand-700");
    if (icon) icon.classList.remove("text-slate-500");
  } else if (viewName === "papelera" && ui.navPapelera) {
    ui.navPapelera.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
    ui.navPapelera.classList.remove("text-slate-700");
    const icon = ui.navPapelera.querySelector("span");
    if (icon) icon.classList.add("text-brand-700");
    if (icon) icon.classList.remove("text-slate-500");
  } else if (viewName === "miramiraPedidos" && ui.navMiramiraPedidos) {
    ui.navMiramiraPedidos.classList.add("bg-brand-50", "text-brand-800", "ring-brand-100");
    ui.navMiramiraPedidos.classList.remove("text-slate-700");
    const icon = ui.navMiramiraPedidos.querySelector("span");
    if (icon) icon.classList.add("text-brand-700");
    if (icon) icon.classList.remove("text-slate-500");
  }

  // Renderizar la vista correspondiente
  if (viewName === "ingresos") {
    renderIngresos();
  } else if (viewName === "gastos") {
    renderGastos();
  } else if (viewName === "cierresDiarios") {
    renderCierresDiarios();
  } else if (viewName === "papelera") {
    renderPapelera();
    populateTrashFilters();
  } else if (viewName === "dashboard") {
    render();
  }
}

function getUsuariosFromEntries(entries) {
  const usuarios = new Set();
  for (const e of entries) {
    if (e.createdBy) usuarios.add(e.createdBy);
  }
  return Array.from(usuarios).sort();
}

function populateUsuarioFilters() {
  const all = loadEntries();
  const usuarios = getUsuariosFromEntries(all);
  
  // Populate ingresos tienda filter
  if (ui.filterIngresosTienda) {
    const currentValue = ui.filterIngresosTienda.value;
    ui.filterIngresosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const assignedStore = typeof authManager !== "undefined" && authManager ? authManager.getAssignedStore() : null;
    const availableStores = assignedStore ? STORES.filter((s) => s.id === assignedStore) : STORES;
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterIngresosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterIngresosTienda.value = currentValue;
  }
  
  // Populate ingresos usuario filter
  if (ui.filterIngresosUsuario) {
    const currentValue = ui.filterIngresosUsuario.value;
    ui.filterIngresosUsuario.innerHTML = '<option value="">Todos</option>';
    for (const u of usuarios) {
      const opt = document.createElement("option");
      opt.value = u;
      opt.textContent = u;
      ui.filterIngresosUsuario.appendChild(opt);
    }
    if (currentValue) ui.filterIngresosUsuario.value = currentValue;
  }
  
  // Populate gastos tienda filter
  if (ui.filterGastosTienda) {
    const currentValue = ui.filterGastosTienda.value;
    ui.filterGastosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const assignedStore = typeof authManager !== "undefined" && authManager ? authManager.getAssignedStore() : null;
    const availableStores = assignedStore ? STORES.filter((s) => s.id === assignedStore) : STORES;
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterGastosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterGastosTienda.value = currentValue;
  }
  
  // Populate gastos usuario filter
  if (ui.filterGastosUsuario) {
    const currentValue = ui.filterGastosUsuario.value;
    ui.filterGastosUsuario.innerHTML = '<option value="">Todos</option>';
    for (const u of usuarios) {
      const opt = document.createElement("option");
      opt.value = u;
      opt.textContent = u;
      ui.filterGastosUsuario.appendChild(opt);
    }
    if (currentValue) ui.filterGastosUsuario.value = currentValue;
  }
  
}

function filterAndSortIngresos(entries, sortField = null, sortDir = null) {
  let filtered = entries.filter(
    (e) => 
      e.entryType === "daily_close" ||  // Incluir todos los cierres diarios
      (e.entryType === "income" && (e.incomeAmount || 0) > 0) ||
      (e.entryType === "expense_refund" && (e.refundAmount || 0) > 0)
  );

  // Filtro por tienda
  if (ui.filterIngresosTienda && ui.filterIngresosTienda.value && ui.filterIngresosTienda.value !== "ALL") {
    filtered = filtered.filter((e) => e.storeId === ui.filterIngresosTienda.value);
  }

  // Filtro por período
  let fechaDesde = null;
  let fechaHasta = null;
  if (ui.filterIngresosPeriodo) {
    const periodo = ui.filterIngresosPeriodo.value;
    if (periodo === "custom") {
      if (ui.filterIngresosFechaDesde && ui.filterIngresosFechaDesde.value) {
        fechaDesde = ui.filterIngresosFechaDesde.value;
      }
      if (ui.filterIngresosFechaHasta && ui.filterIngresosFechaHasta.value) {
        fechaHasta = ui.filterIngresosFechaHasta.value;
      }
    } else {
      const range = getPeriodRange(periodo);
      fechaDesde = range.startISO;
      fechaHasta = range.endISO;
    }
  }
  if (fechaDesde) {
    filtered = filtered.filter((e) => e.date >= fechaDesde);
  }
  if (fechaHasta) {
    filtered = filtered.filter((e) => e.date <= fechaHasta);
  }

  // Filtro por tipo
  if (ui.filterIngresosTipo && ui.filterIngresosTipo.value) {
    filtered = filtered.filter((e) => e.entryType === ui.filterIngresosTipo.value);
  }

  // Filtro por categoría (solo para income)
  if (ui.filterIngresosCategoria && ui.filterIngresosCategoria.value) {
    filtered = filtered.filter((e) => {
      if (e.entryType === "income") {
        return e.incomeCategory === ui.filterIngresosCategoria.value;
      }
      return true; // daily_close y expense_refund no tienen categoría de ingreso
    });
  }

  // Filtro por usuario
  if (ui.filterIngresosUsuario && ui.filterIngresosUsuario.value) {
    filtered = filtered.filter((e) => e.createdBy === ui.filterIngresosUsuario.value);
  }

  // Ordenación
  const sortFieldToUse = sortField || (window.sortIngresosField || "fecha");
  const sortDirToUse = sortDir !== null ? sortDir : (window.sortIngresosDir !== undefined ? window.sortIngresosDir : "desc");
  
  if (sortFieldToUse === "fecha") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => b.date.localeCompare(a.date) || b.updatedAt - a.updatedAt);
    } else {
      filtered.sort((a, b) => a.date.localeCompare(b.date) || a.updatedAt - b.updatedAt);
    }
  } else if (sortFieldToUse === "tipo") {
    const getTipoLabel = (e) => {
      if (e.entryType === "daily_close") return "Cierre diario";
      if (e.entryType === "income") return "Ingreso";
      if (e.entryType === "expense_refund") return "Devolución";
      return "";
    };
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => getTipoLabel(a).localeCompare(getTipoLabel(b)));
    } else {
      filtered.sort((a, b) => getTipoLabel(b).localeCompare(getTipoLabel(a)));
    }
  } else if (sortFieldToUse === "tienda") {
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => storeName(a.storeId).localeCompare(storeName(b.storeId)));
    } else {
      filtered.sort((a, b) => storeName(b.storeId).localeCompare(storeName(a.storeId)));
    }
  } else if (sortFieldToUse === "importe") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => {
        const aVal = a.entryType === "daily_close" ? (a.sales || 0) : a.entryType === "income" ? (a.incomeAmount || 0) : (a.refundAmount || 0);
        const bVal = b.entryType === "daily_close" ? (b.sales || 0) : b.entryType === "income" ? (b.incomeAmount || 0) : (b.refundAmount || 0);
        return bVal - aVal;
      });
    } else {
      filtered.sort((a, b) => {
        const aVal = a.entryType === "daily_close" ? (a.sales || 0) : a.entryType === "income" ? (a.incomeAmount || 0) : (a.refundAmount || 0);
        const bVal = b.entryType === "daily_close" ? (b.sales || 0) : b.entryType === "income" ? (b.incomeAmount || 0) : (b.refundAmount || 0);
        return aVal - bVal;
      });
    }
  } else if (sortFieldToUse === "categoria") {
    const getCategoria = (e) => {
      if (e.entryType === "income" && e.incomeCategory) {
        const catMap = {
          servicios_financieros: "Servicios financieros",
          ventas: "Ventas",
          otros: "Otros"
        };
        return catMap[e.incomeCategory] || e.incomeCategory;
      }
      return "";
    };
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => getCategoria(a).localeCompare(getCategoria(b)));
    } else {
      filtered.sort((a, b) => getCategoria(b).localeCompare(getCategoria(a)));
    }
  } else if (sortFieldToUse === "concepto") {
    const getConcepto = (e) => {
      if (e.entryType === "daily_close") {
        return e.notes || "Cierre diario";
      } else if (e.entryType === "income") {
        return (e.incomeConcept || e.incomeReason || "").trim() || "Ingreso";
      } else if (e.entryType === "expense_refund") {
        return e.refundConcept || "Devolución de gasto";
      }
      return "";
    };
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => getConcepto(a).localeCompare(getConcepto(b)));
    } else {
      filtered.sort((a, b) => getConcepto(b).localeCompare(getConcepto(a)));
    }
  } else if (sortFieldToUse === "usuario") {
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => {
        const aUser = a.createdBy || "";
        const bUser = b.createdBy || "";
        return aUser.localeCompare(bUser);
      });
    } else {
      filtered.sort((a, b) => {
        const aUser = a.createdBy || "";
        const bUser = b.createdBy || "";
        return bUser.localeCompare(aUser);
      });
    }
  }

  return filtered;
}

function renderIngresos() {
  if (!ui.ingresosTbody) return;
  populateUsuarioFilters();
  const all = loadEntries();
  const ingresos = filterAndSortIngresos(all);
  
  // Actualizar indicadores de ordenación
  const ingresosTable = ui.viewIngresos ? ui.viewIngresos.querySelector("table thead") : null;
  if (ingresosTable) {
    const sortField = window.sortIngresosField || "fecha";
    const sortDir = window.sortIngresosDir !== undefined ? window.sortIngresosDir : "desc";
    ingresosTable.querySelectorAll("th[data-sort]").forEach((header) => {
      if (header.dataset.sort === sortField) {
        header.dataset.sortDir = sortDir;
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = sortDir === "desc" ? "↓" : "↑";
      } else {
        header.dataset.sortDir = "";
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = "";
      }
    });
  }

  ui.ingresosTbody.innerHTML = "";
  if (ingresos.length === 0) {
    ui.ingresosTbody.innerHTML = `
      <tr>
        <td class="px-3 py-6 text-center text-slate-500" colspan="9">
          No hay registros de ingresos que coincidan con los filtros.
        </td>
      </tr>
    `;
    return;
  }

  for (const e of ingresos) {
    if (e.entryType === "daily_close") {
      // Para cierre diario, crear dos filas: una para efectivo y otra para banco
      const cashCounted = calculateCashTotal(e.cashCount || {});
      const cashInitial = Number(e.cashInitial || 0);
      const cashExpenses = Number(e.cashExpenses || 0);
      const computedCashSales = round2(cashCounted - cashInitial + cashExpenses);
      const tpv = Number(e.tpv || 0);
      
      const concepto = e.notes || "Cierre diario";
      const tipoLabel = "Cierre diario";
      const tipoColor = "bg-brand-50 text-brand-700 ring-brand-100";
      
      // Fila para efectivo (siempre se muestra, incluso si es 0)
      const trEfectivo = document.createElement("tr");
      trEfectivo.className = "hover:bg-slate-50";
      trEfectivo.innerHTML = `
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
        <td class="whitespace-nowrap px-3 py-3">
          <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${tipoColor}">
            ${tipoLabel}
          </span>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
        <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">${euro.format(computedCashSales)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">—</td>
        <td class="max-w-[360px] px-3 py-3 text-slate-600">
          <div class="truncate">${escapeHtml(concepto)} (Efectivo)</div>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${e.createdBy || "—"}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">Efectivo</td>
        <td class="whitespace-nowrap px-3 py-3 text-right">
          <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
            Editar
          </button>
          <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
            Borrar
          </button>
        </td>
      `;
      ui.ingresosTbody.appendChild(trEfectivo);
      
      // Fila para banco (TPV) (siempre se muestra, incluso si es 0)
      const trBanco = document.createElement("tr");
      trBanco.className = "hover:bg-slate-50";
      trBanco.innerHTML = `
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
        <td class="whitespace-nowrap px-3 py-3">
          <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${tipoColor}">
            ${tipoLabel}
          </span>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
        <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">${euro.format(tpv)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">—</td>
        <td class="max-w-[360px] px-3 py-3 text-slate-600">
          <div class="truncate">${escapeHtml(concepto)} (Banco/TPV)</div>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${e.createdBy || "—"}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">Banco</td>
        <td class="whitespace-nowrap px-3 py-3 text-right">
          <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
            Editar
          </button>
          <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
            Borrar
          </button>
        </td>
      `;
      ui.ingresosTbody.appendChild(trBanco);
    } else {
      // Para otros tipos de ingresos (income, expense_refund), mantener el comportamiento original
      const tr = document.createElement("tr");
      tr.className = "hover:bg-slate-50";
      let importe = 0;
      let concepto = "";
      let tipoLabel = "";
      let tipoColor = "";

      let categoria = "—";
      let formaPago = "—";
      
      if (e.entryType === "income") {
        importe = e.incomeAmount || 0;
        concepto = (e.incomeConcept || e.incomeReason || "").trim() || "Ingreso";
        tipoLabel = "Ingreso";
        tipoColor = "bg-emerald-50 text-emerald-700 ring-emerald-100";
        if (e.incomeCategory) {
          const catMap = {
            servicios_financieros: "Servicios financieros",
            ventas: "Ventas",
            otros: "Otros"
          };
          categoria = catMap[e.incomeCategory] || e.incomeCategory;
        }
        formaPago = "—";
      } else if (e.entryType === "expense_refund") {
        importe = e.refundAmount || 0;
        concepto = e.refundConcept || "Devolución de gasto";
        tipoLabel = "Devolución";
        tipoColor = "bg-amber-50 text-amber-700 ring-amber-100";
        formaPago = "—";
      }

      tr.innerHTML = `
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
        <td class="whitespace-nowrap px-3 py-3">
          <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${tipoColor}">
            ${tipoLabel}
          </span>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
        <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">${euro.format(importe)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${categoria}</td>
        <td class="max-w-[360px] px-3 py-3 text-slate-600">
          <div class="truncate">${escapeHtml(concepto)}</div>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${e.createdBy || "—"}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${formaPago}</td>
        <td class="whitespace-nowrap px-3 py-3 text-right">
          <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
            Editar
          </button>
          <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
            Borrar
          </button>
        </td>
      `;
      ui.ingresosTbody.appendChild(tr);
    }
  }
}

function filterAndSortGastos(entries, sortField = null, sortDir = null) {
  let filtered = entries.filter(
    (e) =>
      (e.entryType === "daily_close" && (e.expenses || 0) > 0) ||
      (e.entryType === "expense" && (e.expenseAmount || 0) > 0)
      // expense_refund ya no aparece en gastos, solo en ingresos
  );

  // Filtro por tienda
  if (ui.filterGastosTienda && ui.filterGastosTienda.value && ui.filterGastosTienda.value !== "ALL") {
    filtered = filtered.filter((e) => e.storeId === ui.filterGastosTienda.value);
  }

  // Filtro por período
  let fechaDesde = null;
  let fechaHasta = null;
  if (ui.filterGastosPeriodo) {
    const periodo = ui.filterGastosPeriodo.value;
    if (periodo === "custom") {
      if (ui.filterGastosFechaDesde && ui.filterGastosFechaDesde.value) {
        fechaDesde = ui.filterGastosFechaDesde.value;
      }
      if (ui.filterGastosFechaHasta && ui.filterGastosFechaHasta.value) {
        fechaHasta = ui.filterGastosFechaHasta.value;
      }
    } else {
      const range = getPeriodRange(periodo);
      fechaDesde = range.startISO;
      fechaHasta = range.endISO;
    }
  }
  if (fechaDesde) {
    filtered = filtered.filter((e) => e.date >= fechaDesde);
  }
  if (fechaHasta) {
    filtered = filtered.filter((e) => e.date <= fechaHasta);
  }

  // Filtro por tipo
  if (ui.filterGastosTipo && ui.filterGastosTipo.value) {
    filtered = filtered.filter((e) => e.entryType === ui.filterGastosTipo.value);
  }

  // Filtro por categoría (solo para expense)
  if (ui.filterGastosCategoria && ui.filterGastosCategoria.value) {
    filtered = filtered.filter((e) => {
      if (e.entryType === "expense") {
        return e.expenseCategory === ui.filterGastosCategoria.value;
      }
      return true; // daily_close y expense_refund no tienen categoría de gasto
    });
  }

  // Filtro por usuario
  if (ui.filterGastosUsuario && ui.filterGastosUsuario.value) {
    filtered = filtered.filter((e) => e.createdBy === ui.filterGastosUsuario.value);
  }

  // Ordenación
  const sortFieldToUse = sortField || (window.sortGastosField || "fecha");
  const sortDirToUse = sortDir !== null ? sortDir : (window.sortGastosDir !== undefined ? window.sortGastosDir : "desc");
  
  if (sortFieldToUse === "fecha") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => b.date.localeCompare(a.date) || b.updatedAt - a.updatedAt);
    } else {
      filtered.sort((a, b) => a.date.localeCompare(b.date) || a.updatedAt - b.updatedAt);
    }
  } else if (sortFieldToUse === "tipo") {
    const getTipoLabel = (e) => {
      if (e.entryType === "daily_close") return "Cierre diario";
      if (e.entryType === "expense") return "Gasto";
      return "";
    };
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => getTipoLabel(a).localeCompare(getTipoLabel(b)));
    } else {
      filtered.sort((a, b) => getTipoLabel(b).localeCompare(getTipoLabel(a)));
    }
  } else if (sortFieldToUse === "tienda") {
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => storeName(a.storeId).localeCompare(storeName(b.storeId)));
    } else {
      filtered.sort((a, b) => storeName(b.storeId).localeCompare(storeName(a.storeId)));
    }
  } else if (sortFieldToUse === "importe") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => {
        const aVal = a.entryType === "daily_close" ? (a.expenses || 0) : a.entryType === "expense" ? (a.expenseAmount || 0) : -(a.refundAmount || 0);
        const bVal = b.entryType === "daily_close" ? (b.expenses || 0) : b.entryType === "expense" ? (b.expenseAmount || 0) : -(b.refundAmount || 0);
        return bVal - aVal;
      });
    } else {
      filtered.sort((a, b) => {
        const aVal = a.entryType === "daily_close" ? (a.expenses || 0) : a.entryType === "expense" ? (a.expenseAmount || 0) : -(a.refundAmount || 0);
        const bVal = b.entryType === "daily_close" ? (b.expenses || 0) : b.entryType === "expense" ? (b.expenseAmount || 0) : -(b.refundAmount || 0);
        return aVal - bVal;
      });
    }
  }

  return filtered;
}

function renderGastos() {
  if (!ui.gastosTbody) return;
  populateUsuarioFilters();
  const all = loadEntries();
  const gastos = filterAndSortGastos(all);
  
  // Actualizar indicadores de ordenación
  const gastosTable = ui.viewGastos ? ui.viewGastos.querySelector("table thead") : null;
  if (gastosTable) {
    const sortField = window.sortGastosField || "fecha";
    const sortDir = window.sortGastosDir !== undefined ? window.sortGastosDir : "desc";
    gastosTable.querySelectorAll("th[data-sort]").forEach((header) => {
      if (header.dataset.sort === sortField) {
        header.dataset.sortDir = sortDir;
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = sortDir === "desc" ? "↓" : "↑";
      } else {
        header.dataset.sortDir = "";
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = "";
      }
    });
  }

  ui.gastosTbody.innerHTML = "";
  if (gastos.length === 0) {
    ui.gastosTbody.innerHTML = `
      <tr>
        <td class="px-3 py-6 text-center text-slate-500" colspan="10">
          No hay registros de gastos que coincidan con los filtros.
        </td>
      </tr>
    `;
    return;
  }

  for (const e of gastos) {
    const tr = document.createElement("tr");
    tr.className = "hover:bg-slate-50";
    let importe = 0;
    let concepto = "";
    let tipoLabel = "";
    let tipoColor = "";

    let categoria = "—";
    let formaPago = "—";
    if (e.entryType === "daily_close") {
      importe = e.expenses || 0;
      concepto = e.notes || "Gastos del cierre diario";
      tipoLabel = "Cierre diario";
      tipoColor = "bg-brand-50 text-brand-700 ring-brand-100";
      // Para cierre diario, los gastos son en efectivo
      formaPago = "Efectivo";
    } else if (e.entryType === "expense") {
      importe = e.expenseAmount || 0;
      concepto = (e.expenseConcept || "").trim() || "Gasto";
      tipoLabel = "Gasto";
      tipoColor = "bg-rose-50 text-rose-700 ring-rose-100";
      if (e.expenseCategory) {
        const catMap = {
          alquiler: "Alquiler",
          impuestos: "Impuestos",
          seguridad_social: "Seguridad social",
          suministros: "Suministros",
          servicios_profesionales: "Servicios profesionales",
          sueldos: "Sueldos",
          miramira: "Miramira",
          mercaderia: "Mercadería",
          equipamiento: "Equipamiento",
          otros: "Otros"
        };
        categoria = catMap[e.expenseCategory] || e.expenseCategory;
      }
      // Mostrar la forma de pago del gasto
      if (e.expensePaymentMethod === "cash") {
        formaPago = "Efectivo";
      } else if (e.expensePaymentMethod === "bank") {
        formaPago = "Banco";
      } else {
        formaPago = "—";
      }
    } else if (e.entryType === "expense_refund") {
      importe = -(e.refundAmount || 0);
      concepto = e.refundConcept || "Devolución de gasto";
      tipoLabel = "Devolución";
      tipoColor = "bg-amber-50 text-amber-700 ring-amber-100";
      formaPago = "—";
    }

    tr.innerHTML = `
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
      <td class="whitespace-nowrap px-3 py-3">
        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ${tipoColor}">
          ${tipoLabel}
        </span>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-semibold ${importe < 0 ? "text-emerald-700" : "text-slate-900"}">${euro.format(importe)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${categoria}</td>
      <td class="max-w-[360px] px-3 py-3 text-slate-600">
        <div class="truncate">${escapeHtml(concepto)}</div>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${e.createdBy || "—"}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${formaPago}</td>
      <td class="whitespace-nowrap px-3 py-3">
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${procedenciaClass}">${procedencia}</span>
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-right">
        <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
          Editar
        </button>
        <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
          Borrar
        </button>
      </td>
    `;
    ui.gastosTbody.appendChild(tr);
  }
}

function filterAndSortCierresDiarios(entries, sortField = null, sortDir = null) {
  // Filtrar solo cierres diarios
  let filtered = entries.filter((e) => e.entryType === "daily_close");

  // Filtro por tienda
  if (ui.filterCierresDiariosTienda && ui.filterCierresDiariosTienda.value && ui.filterCierresDiariosTienda.value !== "ALL") {
    filtered = filtered.filter((e) => e.storeId === ui.filterCierresDiariosTienda.value);
  }

  // Filtro por período
  let fechaDesde = null;
  let fechaHasta = null;
  if (ui.filterCierresDiariosPeriodo) {
    const periodo = ui.filterCierresDiariosPeriodo.value;
    if (periodo === "custom") {
      if (ui.filterCierresDiariosFechaDesde && ui.filterCierresDiariosFechaDesde.value) {
        fechaDesde = ui.filterCierresDiariosFechaDesde.value;
      }
      if (ui.filterCierresDiariosFechaHasta && ui.filterCierresDiariosFechaHasta.value) {
        fechaHasta = ui.filterCierresDiariosFechaHasta.value;
      }
    } else {
      const range = getPeriodRange(periodo);
      fechaDesde = range.startISO;
      fechaHasta = range.endISO;
    }
  }
  if (fechaDesde) {
    filtered = filtered.filter((e) => e.date >= fechaDesde);
  }
  if (fechaHasta) {
    filtered = filtered.filter((e) => e.date <= fechaHasta);
  }

  // Filtro por usuario
  if (ui.filterCierresDiariosUsuario && ui.filterCierresDiariosUsuario.value) {
    filtered = filtered.filter((e) => e.createdBy === ui.filterCierresDiariosUsuario.value);
  }

  // Ordenación
  const sortFieldToUse = sortField || window.sortCierresDiariosField || "fecha";
  const sortDirToUse = sortDir !== null ? sortDir : (window.sortCierresDiariosDir !== undefined ? window.sortCierresDiariosDir : "desc");

  if (sortFieldToUse === "fecha") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => b.date.localeCompare(a.date));
    } else {
      filtered.sort((a, b) => a.date.localeCompare(b.date));
    }
  } else if (sortFieldToUse === "tienda") {
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => storeName(a.storeId).localeCompare(storeName(b.storeId)));
    } else {
      filtered.sort((a, b) => storeName(b.storeId).localeCompare(storeName(a.storeId)));
    }
  } else if (sortFieldToUse === "ventas") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => (b.sales || 0) - (a.sales || 0));
    } else {
      filtered.sort((a, b) => (a.sales || 0) - (b.sales || 0));
    }
  } else if (sortFieldToUse === "gastos") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => (b.expenses || 0) - (a.expenses || 0));
    } else {
      filtered.sort((a, b) => (a.expenses || 0) - (b.expenses || 0));
    }
  } else if (sortFieldToUse === "efectivoRetirado") {
    if (sortDirToUse === "desc") {
      filtered.sort((a, b) => {
        const aCashCounted = calculateCashTotal(a.cashCount || {});
        const bCashCounted = calculateCashTotal(b.cashCount || {});
        const aWithdraw = round2(aCashCounted - (a.cashInitial || 0));
        const bWithdraw = round2(bCashCounted - (b.cashInitial || 0));
        return bWithdraw - aWithdraw;
      });
    } else {
      filtered.sort((a, b) => {
        const aCashCounted = calculateCashTotal(a.cashCount || {});
        const bCashCounted = calculateCashTotal(b.cashCount || {});
        const aWithdraw = round2(aCashCounted - (a.cashInitial || 0));
        const bWithdraw = round2(bCashCounted - (b.cashInitial || 0));
        return aWithdraw - bWithdraw;
      });
    }
  } else if (sortFieldToUse === "usuario") {
    if (sortDirToUse === "asc") {
      filtered.sort((a, b) => (a.createdBy || "").localeCompare(b.createdBy || ""));
    } else {
      filtered.sort((a, b) => (b.createdBy || "").localeCompare(a.createdBy || ""));
    }
  }

  return filtered;
}

function renderCierresDiarios() {
  if (!ui.cierresDiariosTbody) return;
  populateUsuarioFilters();
  const all = loadEntries();
  const cierres = filterAndSortCierresDiarios(all);
  
  // Actualizar indicadores de ordenación
  const cierresTable = ui.viewCierresDiarios ? ui.viewCierresDiarios.querySelector("table thead") : null;
  if (cierresTable) {
    const sortField = window.sortCierresDiariosField || "fecha";
    const sortDir = window.sortCierresDiariosDir !== undefined ? window.sortCierresDiariosDir : "desc";
    cierresTable.querySelectorAll("th[data-sort]").forEach((header) => {
      if (header.dataset.sort === sortField) {
        header.dataset.sortDir = sortDir;
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = sortDir === "desc" ? "↓" : "↑";
      } else {
        header.dataset.sortDir = "";
        const indicator = header.querySelector("[data-sort-indicator]");
        if (indicator) indicator.textContent = "";
      }
    });
  }

  // Poblar filtros de tienda
  if (ui.filterCierresDiariosTienda) {
    const currentValue = ui.filterCierresDiariosTienda.value;
    ui.filterCierresDiariosTienda.innerHTML = '<option value="ALL">Todas las tiendas</option>';
    const assignedStore = typeof authManager !== "undefined" && authManager ? authManager.getAssignedStore() : null;
    const availableStores = assignedStore ? STORES.filter((s) => s.id === assignedStore) : STORES;
    for (const store of availableStores) {
      const opt = document.createElement("option");
      opt.value = store.id;
      opt.textContent = store.name;
      ui.filterCierresDiariosTienda.appendChild(opt);
    }
    if (currentValue) ui.filterCierresDiariosTienda.value = currentValue;
  }

  // Poblar filtros de usuario
  if (ui.filterCierresDiariosUsuario) {
    const usuarios = getUsuariosFromEntries(all);
    const currentValue = ui.filterCierresDiariosUsuario.value;
    ui.filterCierresDiariosUsuario.innerHTML = '<option value="">Todos</option>';
    usuarios.forEach((user) => {
      const option = document.createElement("option");
      option.value = user;
      option.textContent = user;
      ui.filterCierresDiariosUsuario.appendChild(option);
    });
    if (currentValue) ui.filterCierresDiariosUsuario.value = currentValue;
  }

  ui.cierresDiariosTbody.innerHTML = "";
  if (cierres.length === 0) {
    ui.cierresDiariosTbody.innerHTML = `
      <tr>
        <td class="px-3 py-6 text-center text-slate-500" colspan="9">
          No hay cierres diarios para este filtro. Añade uno con "Añadir cierre".
        </td>
      </tr>
    `;
    return;
  }

  for (const e of cierres) {
    const cashCounted = calculateCashTotal(e.cashCount || {});
    const cashInitial = Number(e.cashInitial || 0);
    const cashExpenses = Number(e.cashExpenses || 0);
    const computedCashSales = round2(cashCounted - cashInitial + cashExpenses);
    const tpv = Number(e.tpv || 0);
    const sales = Number(e.sales || 0);
    const expenses = Number(e.expenses || 0);
    const withdraw = round2(cashCounted - cashInitial);

    const tr = document.createElement("tr");
    tr.className = "hover:bg-slate-50";
    tr.innerHTML = `
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${e.date}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-700">${storeName(e.storeId)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">${euro.format(sales)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-semibold text-rose-700">${euro.format(expenses)}</td>
      <td class="whitespace-nowrap px-3 py-3 font-semibold ${withdraw > 0 ? "text-slate-900" : "text-slate-500"}">${euro.format(withdraw > 0 ? withdraw : 0)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${euro.format(computedCashSales)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${euro.format(tpv)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-slate-600">${e.createdBy || "—"}</td>
      <td class="whitespace-nowrap px-3 py-3 text-right">
        <button data-action="edit" data-id="${e.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
          Editar
        </button>
        <button data-action="delete" data-id="${e.id}" class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
          Borrar
        </button>
      </td>
    `;
    ui.cierresDiariosTbody.appendChild(tr);
  }
}

/** -------- Export -------- */

function exportCSV(entries) {
  const header = [
    "fecha",
    "tipo",
    "tienda",
    "ventas",
    "gastos",
    "margen",
    "efectivo_inicial",
    "tpv",
    "gastos_efectivo",
    "efectivo_contado",
    "vales_entrada",
    "vales_salida",
    "vales_resultado",
    "shopify_efectivo",
    "shopify_tpv",
    "discrepancia_efectivo",
    "discrepancia_tpv",
    "retirar_efectivo",
    "gastos_detalle",
    "categoria_gasto",
    "metodo_pago_gasto",
    "categoria_ingreso",
    "notas",
  ];
  const lines = [header.join(",")];
  for (const e of entries) {
    const margin = (e.sales || 0) - (e.expenses || 0);
    const cashCounted = calculateCashTotal(e.cashCount || {});
    const computedCashSales = round2(cashCounted - (e.cashInitial || 0) + (e.cashExpenses || 0));
    const cashDiff = e.shopifyCash !== null ? round2(computedCashSales - e.shopifyCash) : null;
    const tpvDiff = e.shopifyTpv !== null ? round2((e.tpv || 0) - e.shopifyTpv) : null;
    const withdraw = round2(cashCounted - (e.cashInitial || 0));
    const expenseDetail = (e.expenseItems || [])
      .map((item) => `${item.concept}:${item.amount}`)
      .join("; ");
    const row = [
      e.date,
      e.entryType || "daily_close",
      `"${String(storeName(e.storeId)).replaceAll('"', '""')}"`,
      String(e.sales || 0),
      String(e.expenses || 0),
      String(margin),
      String(e.cashInitial || 0),
      String(e.tpv || 0),
      String(e.cashExpenses || 0),
      String(cashCounted),
      String(e.vouchersIn || 0),
      String(e.vouchersOut || 0),
      String(e.vouchersResult || 0),
      e.shopifyCash !== null ? String(e.shopifyCash) : "",
      e.shopifyTpv !== null ? String(e.shopifyTpv) : "",
      cashDiff !== null ? String(cashDiff) : "",
      tpvDiff !== null ? String(tpvDiff) : "",
      String(withdraw),
      `"${expenseDetail.replaceAll('"', '""')}"`,
      e.expenseCategory || "",
      e.expensePaymentMethod || "",
      e.incomeCategory || "",
      `"${String(e.notes || "").replaceAll('"', '""')}"`,
    ];
    lines.push(row.join(","));
  }
  const blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `miramira_registros_${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}
  const header = [
    "fecha",
    "tipo",
    "tienda",
    "ventas",
    "gastos",
    "margen",
    "efectivo_inicial",
    "tpv",
    "gastos_efectivo",
    "efectivo_contado",
    "vales_entrada",
    "vales_salida",
    "vales_resultado",
    "shopify_efectivo",
    "shopify_tpv",
    "discrepancia_efectivo",
    "discrepancia_tpv",
    "retirar_efectivo",
    "gastos_detalle",
    "categoria_gasto",
    "metodo_pago_gasto",
    "categoria_ingreso",
    "notas",
  ];
  const lines = [header.join(",")];
  for (const e of entries) {
    const margin = (e.sales || 0) - (e.expenses || 0);
    const cashCounted = calculateCashTotal(e.cashCount || {});
    const computedCashSales = round2(cashCounted - (e.cashInitial || 0) + (e.cashExpenses || 0));
    const cashDiff = e.shopifyCash !== null ? round2(computedCashSales - e.shopifyCash) : null;
    const tpvDiff = e.shopifyTpv !== null ? round2((e.tpv || 0) - e.shopifyTpv) : null;
    const withdraw = round2(cashCounted - (e.cashInitial || 0));
    const expenseDetail = (e.expenseItems || [])
      .map((item) => `${item.concept}:${item.amount}`)
      .join("; ");
    const row = [
      e.date,
      e.entryType || "daily_close",
      `"${String(storeName(e.storeId)).replaceAll('"', '""')}"`,
      String(e.sales || 0),
      String(e.expenses || 0),
      String(margin),
      String(e.cashInitial || 0),
      String(e.tpv || 0),
      String(e.cashExpenses || 0),
      String(cashCounted),
      String(e.vouchersIn || 0),
      String(e.vouchersOut || 0),
      String(e.vouchersResult || 0),
      e.shopifyCash !== null ? String(e.shopifyCash) : "",
      e.shopifyTpv !== null ? String(e.shopifyTpv) : "",
      cashDiff !== null ? String(cashDiff) : "",
      tpvDiff !== null ? String(tpvDiff) : "",
      String(withdraw),
      `"${expenseDetail.replaceAll('"', '""')}"`,
      e.expenseCategory || "",
      e.expensePaymentMethod || "",
      e.incomeCategory || "",
      `"${String(e.notes || "").replaceAll('"', '""')}"`,
    ];
    lines.push(row.join(","));
  }
  const blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `miramira_registros_${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

/** -------- App state + main render -------- */

const state = {
  storeId: "ALL",
  period: "this_month",
  search: "",
};

function getViewModel() {
  const all = loadEntries();
  const { startISO, endISO } = getPeriodRange(state.period);
  const { prevStartISO, prevEndISO } = comparePeriodRange(state.period, startISO, endISO);

  const filtered = filterEntries(all, { storeId: state.storeId, startISO, endISO, search: state.search });
  const prevFiltered = filterEntries(all, { storeId: state.storeId, startISO: prevStartISO, endISO: prevEndISO, search: "" });

  return {
    all,
    filtered,
    prevFiltered,
    startISO,
    endISO,
    prevStartISO,
    prevEndISO,
  };
}

function render() {
  const vm = getViewModel();
  const curSum = sumEntries(vm.filtered);
  const prevSum = sumEntries(vm.prevFiltered);

  renderKPIs(curSum, prevSum, vm.filtered.length, vm.startISO, vm.endISO);
  renderCharts(vm.filtered, state.storeId, vm.startISO, vm.endISO);
  renderTable(vm.filtered);
}

/** -------- Events -------- */

function upsertEntry(newEntry) {
  const entries = loadEntries();
  const idx = entries.findIndex((e) => e.id === newEntry.id);
  const currentUser = typeof authManager !== "undefined" && authManager && authManager.isAuthenticated() 
    ? authManager.getCurrentUser() 
    : null;
  const userName = currentUser ? (currentUser.name || currentUser.username) : "Sistema";
  const userId = currentUser ? currentUser.id : "system";
  
  if (idx >= 0) {
    // Es una actualización - registrar cambios
    const oldEntry = entries[idx];
    const changes = {};
    
    // Comparar campos importantes
    const fieldsToTrack = [
      "storeId", "date", "entryType", "sales", "expenses", "notes",
      "cashInitial", "tpv", "expenseAmount", "expenseCategory", "expenseConcept",
      "incomeAmount", "incomeCategory", "incomeConcept", "refundAmount", "refundConcept"
    ];
    
    fieldsToTrack.forEach(field => {
      if (oldEntry[field] !== newEntry[field]) {
        changes[field] = {
          old: oldEntry[field],
          new: newEntry[field]
        };
      }
    });
    
    // Agregar entrada al historial
    if (!newEntry.history) newEntry.history = [];
    newEntry.history.push({
      action: "updated",
      userId: userId,
      userName: userName,
      timestamp: Date.now(),
      changes: Object.keys(changes).length > 0 ? changes : undefined
    });
    
    newEntry.updatedBy = userId;
    entries[idx] = newEntry;
  } else {
    // Es una creación
    if (!newEntry.history) newEntry.history = [];
    newEntry.history.push({
      action: "created",
      userId: userId,
      userName: userName,
      timestamp: Date.now()
    });
    entries.push(newEntry);
  }
  saveEntries(entries);
}

function deleteEntry(entryId) {
  const currentUser = typeof authManager !== "undefined" && authManager && authManager.isAuthenticated() 
    ? authManager.getCurrentUser() 
    : null;
  const deletedBy = currentUser ? currentUser.id : "system";
  
  moveToTrash(entryId, deletedBy);
}

function findEntry(entryId) {
  return loadEntries().find((e) => e.id === entryId) || null;
}

function validateUniqueStoreDate(storeId, date, entryId) {
  const entries = loadEntries();
  return !entries.some((e) => e.storeId === storeId && e.date === date && e.id !== entryId);
}

function wireEvents() {
  ui.storeSelect.addEventListener("change", () => {
    state.storeId = ui.storeSelect.value;
    render();
  });

  ui.periodSelect.addEventListener("change", () => {
    state.period = ui.periodSelect.value;
    render();
  });

  ui.searchInput.addEventListener("input", () => {
    state.search = ui.searchInput.value;
    render();
  });

  ui.addEntryBtn.addEventListener("click", () => {
    if (typeof roleManager !== "undefined" && roleManager) {
      const allowed = typeof roleManager.getAllowedCreateTypes === "function" ? roleManager.getAllowedCreateTypes() : [];
      if (allowed.length === 0) {
        alert("No tienes permisos para crear registros.");
        return;
      }
      openModalWithTypeSelection(allowed);
    } else {
      openModalWithTypeSelection();
    }
  });
  ui.closeModalBtn.addEventListener("click", closeModal);
  ui.cancelBtn.addEventListener("click", closeModal);

  ui.formEntryType.addEventListener("change", () => {
    showSectionForType(ui.formEntryType.value);
  });

  ui.formRefundType.addEventListener("change", () => {
    updateRefundTypeVisibility();
  });

  ui.addExpenseItemBtn?.addEventListener("click", () => {
    expenseItemsDraft.push({ id: uuid(), concept: "", amount: 0, paidWithCash: true });
    renderExpenseItems(expenseItemsDraft);
  });

  // Event listeners para división de gastos entre tiendas
  if (ui.formExpenseSplitStores) {
    ui.formExpenseSplitStores.addEventListener("change", () => {
      if (ui.expenseSplitContainer) {
        if (ui.formExpenseSplitStores.checked) {
          ui.expenseSplitContainer.classList.remove("hidden");
          renderExpenseSplitStores();
        } else {
          ui.expenseSplitContainer.classList.add("hidden");
        }
      }
    });
  }

  if (ui.formExpenseAmount) {
    ui.formExpenseAmount.addEventListener("input", () => {
      if (ui.formExpenseSplitStores && ui.formExpenseSplitStores.checked) {
        updateExpenseSplitInputs();
      }
    });
  }

  // Delegación de eventos para los checkboxes de selección de tiendas
  // Usar el contenedor principal para asegurar que funcione incluso cuando el contenedor está oculto inicialmente
  if (ui.expenseSplitContainer) {
    ui.expenseSplitContainer.addEventListener("change", (e) => {
      // Verificar si el evento viene de un checkbox dentro de expenseSplitStoreCheckboxes
      const checkbox = e.target;
      if (checkbox.type === "checkbox" && checkbox.closest("#expenseSplitStoreCheckboxes")) {
        updateExpenseSplitInputs();
      }
    });
  }

  // Delegación de eventos para los inputs de división
  if (ui.expenseSplitStoresList) {
    ui.expenseSplitStoresList.addEventListener("input", (e) => {
      if (e.target.matches("input[type='number']")) {
        updateExpenseSplitTotal();
      }
    });
  }

  // formCashExpenses ahora es editable directamente
  [ui.formCashInitial, ui.formTpv, ui.formCashExpenses, ui.formShopifyCash, ui.formShopifyTpv, ui.formVouchersIn, ui.formVouchersOut].forEach((input) => {
    if (input) {
      input.addEventListener("input", () => {
        updateCashCountTotal();
        updateVouchersResult();
        updateReconciliation();
        updateDailyCloseTotals();
        updateExpenseTotals();
      });
    }
  });

  ui.entryModal.addEventListener("click", (e) => {
    if (e.target === ui.entryModal) closeModal();
  });

  // Navegación con Enter: mover al siguiente campo en lugar de hacer submit
  function findNextEditableField(currentElement) {
    const form = ui.entryForm;
    if (!form) return null;

    // Obtener todos los campos editables del formulario (incluyendo campos dinámicos)
    const allFields = Array.from(
      form.querySelectorAll(
        'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([readonly]):not([disabled]), ' +
        'select:not([disabled]), ' +
        'textarea:not([readonly]):not([disabled])'
      )
    );

    // Filtrar campos visibles (no ocultos por display:none o dentro de secciones ocultas)
    const visibleFields = allFields.filter((field) => {
      const style = window.getComputedStyle(field);
      const parent = field.closest('.hidden');
      const isVisible = style.display !== 'none' && style.visibility !== 'hidden' && !parent;
      return isVisible && !field.disabled && !field.readOnly;
    });

    // Encontrar el índice del campo actual
    const currentIndex = visibleFields.indexOf(currentElement);
    if (currentIndex === -1) return null;

    // Buscar el siguiente campo editable
    for (let i = currentIndex + 1; i < visibleFields.length; i++) {
      const field = visibleFields[i];
      if (!field.disabled && !field.readOnly) {
        return field;
      }
    }

    // Si no hay más campos, no hacer nada
    return null;
  }

  // Agregar event listeners para Enter en todos los campos del formulario
  function setupEnterNavigation() {
    const form = ui.entryForm;
    if (!form) return;

    // Usar delegación de eventos para capturar también campos dinámicos
    form.addEventListener('keydown', (e) => {
      // Solo procesar Enter
      if (e.key !== 'Enter') return;

      const target = e.target;
      
      // Ignorar si es un botón
      if (target.tagName === 'BUTTON') {
        return;
      }

      // Permitir Enter normal en textarea (para saltos de línea)
      if (target.tagName === 'TEXTAREA') {
        return;
      }

      // Si es un select o input, mover al siguiente campo
      if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
        e.preventDefault();
        e.stopPropagation();
        
        const nextField = findNextEditableField(target);
        if (nextField) {
          nextField.focus();
          // Si es un input de texto o número, seleccionar el contenido para facilitar edición
          if (nextField.tagName === 'INPUT' && (nextField.type === 'text' || nextField.type === 'number')) {
            setTimeout(() => nextField.select(), 0);
          }
        } else {
          // Si no hay más campos, enfocar el botón de guardar (pero no hacer submit automático)
          const saveBtn = form.querySelector('button[type="submit"]');
          if (saveBtn) {
            saveBtn.focus();
          }
        }
      }
    }, true); // Usar capture para asegurar que se ejecute antes que otros handlers
  }

  setupEnterNavigation();

  ui.entryForm.addEventListener("submit", (e) => {
    e.preventDefault();
    ui.formError.classList.add("hidden");

    const entryId = ui.entryId.value || uuid();
    const storeId = ui.formStore.value;
    const date = ui.formDate.value;
    const entryType = ui.formEntryType.value;

    if (!storeId) return showFormError("Selecciona una tienda.");
    if (!date) return showFormError("Selecciona una fecha.");

    const now = Date.now();
    const existing = findEntry(entryId);
    const isEdit = Boolean(existing);

    // Seguridad: tienda asignada
    const assignedStore =
      typeof authManager !== "undefined" && authManager ? authManager.getAssignedStore() : null;
    if (assignedStore && storeId !== assignedStore) {
      return showFormError("No puedes crear/editar registros fuera de tu tienda asignada.");
    }

    // Seguridad: permisos (no solo ocultar botones)
    if (typeof roleManager !== "undefined" && roleManager) {
      if (isEdit) {
        if (!roleManager.hasPermission("financial.registros.edit")) return showFormError("No tienes permisos para editar registros.");
      } else {
        if (!roleManager.hasPermission("create")) return showFormError("No tienes permisos para crear registros.");
        if (typeof roleManager.canCreateType === "function" && !roleManager.canCreateType(entryType)) {
          return showFormError("No tienes permisos para crear este tipo de registro.");
        }
      }
    }
    /** @type {Entry} */
    const entry = {
      id: entryId,
      storeId,
      date,
      entryType,
      notes: (ui.formNotes.value || "").trim(),
      sales: 0,
      expenses: 0,
      cashInitial: 0,
      tpv: 0,
      cashExpenses: 0,
      cashCount: {},
      shopifyCash: null,
      shopifyTpv: null,
      vouchersIn: 0,
      vouchersOut: 0,
      vouchersResult: 0,
      expenseItems: [],
      incomeAmount: 0,
      incomeCategory: "",
      incomeConcept: "",
      incomeReason: "",
      expenseAmount: 0,
      expenseCategory: "",
      expensePaymentMethod: "",
      expenseConcept: "",
      expensePaidCash: false,
      refundType: "new",
      refundOriginalId: "",
      refundAmount: 0,
      refundConcept: "",
      createdBy: isEdit ? (existing?.createdBy || "") : (typeof authManager !== "undefined" && authManager && authManager.isAuthenticated() ? (authManager.getCurrentUser()?.id || authManager.getCurrentUser()?.username || "") : ""),
      updatedBy: isEdit ? (typeof authManager !== "undefined" && authManager && authManager.isAuthenticated() ? (authManager.getCurrentUser()?.id || authManager.getCurrentUser()?.username || "") : "") : undefined,
      createdAt: existing?.createdAt || now,
      updatedAt: now,
      history: existing?.history || [],
    };

    if (entryType === "daily_close") {
      const cashInitial = safeNumberFromInput(ui.formCashInitial.value);
      const tpv = safeNumberFromInput(ui.formTpv.value);
      const vouchersIn = safeNumberFromInput(ui.formVouchersIn.value);
      const vouchersOut = safeNumberFromInput(ui.formVouchersOut.value);
      const shopifyCashRaw = ui.formShopifyCash.value.trim();
      const shopifyTpvRaw = ui.formShopifyTpv.value.trim();
      const shopifyCash = shopifyCashRaw ? safeNumberFromInput(shopifyCashRaw) : null;
      const shopifyTpv = shopifyTpvRaw ? safeNumberFromInput(shopifyTpvRaw) : null;
      const cashCount = getCashCountFromForm();

      if (!Number.isFinite(cashInitial) || cashInitial < 0) return showFormError("Efectivo inicial inválido. Usa un número ≥ 0.");
      if (!Number.isFinite(tpv) || tpv < 0) return showFormError("Tarjeta inválida. Usa un número ≥ 0.");
      if (!Number.isFinite(vouchersIn) || vouchersIn < 0) return showFormError("Vales entrada inválido. Usa un número ≥ 0.");
      if (!Number.isFinite(vouchersOut) || vouchersOut < 0) return showFormError("Vales salida inválido. Usa un número ≥ 0.");
      if (shopifyCash !== null && (!Number.isFinite(shopifyCash) || shopifyCash < 0)) return showFormError("Shopify efectivo inválido. Usa un número ≥ 0 o déjalo vacío.");
      if (shopifyTpv !== null && (!Number.isFinite(shopifyTpv) || shopifyTpv < 0)) return showFormError("Shopify tarjeta inválido. Usa un número ≥ 0 o déjalo vacío.");

      const cashCounted = calculateCashTotal(cashCount);
      // El total de gastos ahora se obtiene directamente del campo formCashExpenses
      const cashExpenses = safeNumberFromInput(ui.formCashExpenses.value);
      const totalExpenses = cashExpenses;
      // En cierre diario, estos gastos son en efectivo y NO se editan manualmente
      const computedCashSales = round2(cashCounted - cashInitial + cashExpenses);
      const vouchersResult = round2(vouchersIn - vouchersOut);
      const totalSales = round2(tpv + computedCashSales + vouchersResult);

      entry.sales = totalSales;
      entry.expenses = round2(totalExpenses);
      entry.cashInitial = round2(cashInitial);
      entry.tpv = round2(tpv);
      entry.cashExpenses = cashExpenses;
      entry.cashCount = cashCount;
      entry.shopifyCash = shopifyCash !== null ? round2(shopifyCash) : null;
      entry.shopifyTpv = shopifyTpv !== null ? round2(shopifyTpv) : null;
      entry.vouchersIn = round2(vouchersIn);
      entry.vouchersOut = round2(vouchersOut);
      entry.vouchersResult = vouchersResult;
      entry.expenseItems = getExpenseItemsFromForm();
    } else if (entryType === "expense") {
      const expenseAmount = safeNumberFromInput(ui.formExpenseAmount.value);
      const expenseConcept = (ui.formExpenseConcept.value || "").trim();
      const expenseCategory = (ui.formExpenseCategory.value || "").trim();
      const expensePaymentMethod = (ui.formExpensePaymentMethod.value || "").trim();
      const splitStores = ui.formExpenseSplitStores && ui.formExpenseSplitStores.checked;

      if (!Number.isFinite(expenseAmount) || expenseAmount <= 0) return showFormError("Importe del gasto inválido. Usa un número > 0.");
      if (!expenseCategory) return showFormError("Selecciona la categoría del gasto.");
      if (!expensePaymentMethod) return showFormError("Selecciona el método de pago (banco o efectivo).");
      if (!expenseConcept) return showFormError("Indica el concepto del gasto.");

      if (splitStores) {
        // División entre tiendas
        const splits = getExpenseSplitStores();
        if (splits.length === 0) return showFormError("Debes asignar al menos una tienda.");
        
        // Validar que la suma sea correcta
        const totalSplit = splits.reduce((sum, s) => sum + s.amount, 0);
        if (Math.abs(totalSplit - expenseAmount) >= 0.01) {
          return showFormError(`La suma de las cantidades (${euro.format(totalSplit)}) debe ser igual al importe total (${euro.format(expenseAmount)}).`);
        }

        // Crear un registro por cada tienda
        for (const split of splits) {
          const splitEntry = {
            ...entry,
            id: uuid(),
            storeId: split.storeId,
            expenseAmount: round2(split.amount),
            expenseCategory,
            expensePaymentMethod,
            expenseConcept: `${expenseConcept} (${split.storeName})`,
            expenses: round2(split.amount),
            createdAt: now,
            updatedAt: now,
          };
          upsertEntry(splitEntry);
        }
      } else {
        // Gasto normal (una sola tienda)
        entry.expenseAmount = round2(expenseAmount);
        entry.expenseCategory = expenseCategory;
        entry.expensePaymentMethod = expensePaymentMethod;
        entry.expenseConcept = expenseConcept;
        entry.expenses = round2(expenseAmount);
        upsertEntry(entry);
      }
      
      // Cerrar modal y refrescar (no continuar con el código después)
      closeModal();
      if (ui.viewIngresos && !ui.viewIngresos.classList.contains("hidden")) {
        renderIngresos();
      } else if (ui.viewGastos && !ui.viewGastos.classList.contains("hidden")) {
        renderGastos();
      } else if (ui.viewCierresDiarios && !ui.viewCierresDiarios.classList.contains("hidden")) {
        renderCierresDiarios();
      } else {
        render();
      }
      return; // Salir temprano para no ejecutar el código de abajo
    } else if (entryType === "income") {
      const incomeAmount = safeNumberFromInput(ui.formIncomeAmount.value);
      const incomeCategory = (ui.formIncomeCategory.value || "").trim();
      const incomeConcept = (ui.formIncomeConcept.value || "").trim();

      if (!Number.isFinite(incomeAmount) || incomeAmount <= 0) return showFormError("Importe del ingreso inválido. Usa un número > 0.");
      if (!incomeCategory) return showFormError("Selecciona la categoría del ingreso.");
      if (!incomeConcept) return showFormError("Indica el concepto del ingreso.");

      entry.incomeAmount = round2(incomeAmount);
      entry.incomeCategory = incomeCategory;
      entry.incomeConcept = incomeConcept;
      entry.sales = round2(incomeAmount);
    } else if (entryType === "expense_refund") {
      const refundAmount = safeNumberFromInput(ui.formRefundAmount.value);
      const refundConcept = (ui.formRefundConcept.value || "").trim();
      const refundType = ui.formRefundType.value;

      if (!Number.isFinite(refundAmount) || refundAmount <= 0) return showFormError("Importe de la devolución inválido. Usa un número > 0.");
      if (!refundConcept) return showFormError("Indica el concepto de la devolución.");

      entry.refundAmount = round2(refundAmount);
      entry.refundConcept = refundConcept;
      entry.refundType = refundType;
      entry.refundOriginalId = refundType === "existing" ? (ui.formRefundOriginalId.value || "").trim() : "";
      entry.expenses = round2(-refundAmount);
    }

    if (entryType === "daily_close" && !validateUniqueStoreDate(storeId, date, entryId)) {
      return showFormError("Ya existe un cierre diario para esa tienda y fecha. Edita el existente.");
    }

    upsertEntry(entry);
    closeModal();
    // Refrescar la vista actual
    if (ui.viewIngresos && !ui.viewIngresos.classList.contains("hidden")) {
      renderIngresos();
    } else if (ui.viewGastos && !ui.viewGastos.classList.contains("hidden")) {
      renderGastos();
    } else if (ui.viewCierresDiarios && !ui.viewCierresDiarios.classList.contains("hidden")) {
      renderCierresDiarios();
    } else {
      render();
    }
  });

  ui.entriesTbody.addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    const action = btn.dataset.action;
    const id = btn.dataset.id;
    if (!action || !id) return;

    if (action === "edit") {
      if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("financial.registros.edit")) {
        alert("No tienes permisos para editar registros.");
        return;
      }
      const entry = findEntry(id);
      if (!entry) return;
      openModal("edit", entry);
      return;
    }

    if (action === "delete") {
      if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("delete")) {
        alert("No tienes permisos para borrar registros.");
        return;
      }
      const entry = findEntry(id);
      if (!entry) return;
      const ok = confirm(`¿Borrar el registro del ${entry.date} (${storeName(entry.storeId)})?`);
      if (!ok) return;
      deleteEntry(id);
      render();
    }
  });

  ui.exportBtn.addEventListener("click", () => {
    if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("financial.registros.export")) {
      alert("No tienes permisos para exportar.");
      return;
    }
    const vm = getViewModel();
    exportCSV(vm.filtered);
  });

  // Navegación: Dashboard
  const dashboardLink = document.querySelector("nav a:first-of-type");
  if (dashboardLink) {
    dashboardLink.addEventListener("click", (e) => {
      e.preventDefault();
      showView("dashboard");
    });
  }

  // Navegación: Ingresos
  if (ui.navIngresos) {
    ui.navIngresos.addEventListener("click", (e) => {
      e.preventDefault();
      showView("ingresos");
    });
  }

  // Navegación: Gastos
  if (ui.navGastos) {
    ui.navGastos.addEventListener("click", (e) => {
      e.preventDefault();
      showView("gastos");
    });
  }

  // Navegación: Cierres Diarios
  if (ui.navCierresDiarios) {
    ui.navCierresDiarios.addEventListener("click", (e) => {
      e.preventDefault();
      showView("cierresDiarios");
    });
  }

  // Navegación: Papelera
  if (ui.navPapelera) {
    ui.navPapelera.addEventListener("click", (e) => {
      e.preventDefault();
      showView("papelera");
    });
  }

  // Navegación: Pedidos
  if (ui.navMiramiraPedidos) {
    ui.navMiramiraPedidos.addEventListener("click", (e) => {
      e.preventDefault();
      showView("miramiraPedidos");
    });
  }

  // Filtros de Papelera
  if (ui.filterTrashType) {
    ui.filterTrashType.addEventListener("change", renderPapelera);
  }
  if (ui.filterTrashStore) {
    ui.filterTrashStore.addEventListener("change", renderPapelera);
  }
  if (ui.filterTrashDays) {
    ui.filterTrashDays.addEventListener("change", renderPapelera);
  }

  // Botón vaciar papelera
  if (ui.emptyTrashBtn) {
    ui.emptyTrashBtn.addEventListener("click", () => {
      if (confirm("¿Estás seguro de que quieres vaciar la papelera? Esta acción no se puede deshacer.")) {
        saveTrash([]);
        renderPapelera();
      }
    });
  }

  // Event listeners para acciones de papelera
  if (ui.papeleraTbody) {
    ui.papeleraTbody.addEventListener("click", (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const id = btn.getAttribute("data-id");
      if (!action || !id) return;

      if (action === "restore") {
        if (restoreFromTrash(id)) {
          alert("Registro restaurado correctamente.");
          renderPapelera();
          // Refrescar la vista actual si estamos en dashboard, ingresos o gastos
          if (ui.viewDashboard && ui.viewDashboard.style.display !== "none") {
            render();
          } else if (ui.viewIngresos && !ui.viewIngresos.classList.contains("hidden")) {
            renderIngresos();
          } else if (ui.viewGastos && !ui.viewGastos.classList.contains("hidden")) {
            renderGastos();
          } else if (ui.viewCierresDiarios && !ui.viewCierresDiarios.classList.contains("hidden")) {
            renderCierresDiarios();
          }
        } else {
          alert("Error al restaurar el registro.");
        }
      } else if (action === "delete-permanent") {
        if (confirm("¿Estás seguro de que quieres eliminar permanentemente este registro? Esta acción no se puede deshacer.")) {
          if (permanentlyDeleteFromTrash(id)) {
            renderPapelera();
          } else {
            alert("Error al eliminar el registro.");
          }
        }
      }
    });
  }




  // Botón añadir registro en Ingresos
  if (ui.addEntryIngresosBtn) {
    ui.addEntryIngresosBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager) {
        const roleAllowed = typeof roleManager.getAllowedCreateTypes === "function" ? roleManager.getAllowedCreateTypes() : [];
        if (roleAllowed.length === 0) {
          alert("No tienes permisos para crear registros.");
          return;
        }
        if (!roleAllowed.includes("income")) {
          alert("No tienes permisos para crear ingresos.");
          return;
        }
      }
      openModal("add", null, ["income"]);
    });
  }

  // Botón añadir registro en Gastos
  if (ui.addEntryGastosBtn) {
    ui.addEntryGastosBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager) {
        const roleAllowed = typeof roleManager.getAllowedCreateTypes === "function" ? roleManager.getAllowedCreateTypes() : [];
        if (roleAllowed.length === 0) {
          alert("No tienes permisos para crear registros.");
          return;
        }
        if (!roleAllowed.includes("expense")) {
          alert("No tienes permisos para crear gastos.");
          return;
        }
      }
      openModal("add", null, ["expense"]);
    });
  }

  // Exportar Ingresos
  if (ui.exportIngresosBtn) {
    ui.exportIngresosBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("financial.registros.export")) {
        alert("No tienes permisos para exportar.");
        return;
      }
      const all = loadEntries();
      const ingresos = filterAndSortIngresos(all);
      // Incluir devoluciones de gasto en la exportación de ingresos
      exportCSV(ingresos);
    });
  }

  // Exportar Gastos
  if (ui.exportGastosBtn) {
    ui.exportGastosBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("financial.registros.export")) {
        alert("No tienes permisos para exportar.");
        return;
      }
      const all = loadEntries();
      const gastos = filterAndSortGastos(all);
      exportCSV(gastos);
    });
  }

  // Añadir Cierre Diario
  if (ui.addCierreDiarioBtn) {
    ui.addCierreDiarioBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager) {
        const roleAllowed = typeof roleManager.getAllowedCreateTypes === "function" ? roleManager.getAllowedCreateTypes() : [];
        if (roleAllowed.length === 0) {
          alert("No tienes permisos para crear registros.");
          return;
        }
        if (!roleAllowed.includes("daily_close")) {
          alert("No tienes permisos para crear cierres diarios.");
          return;
        }
      }
      openModal("add", null, ["daily_close"]);
    });
  }

  // Exportar Cierres Diarios
  if (ui.exportCierresDiariosBtn) {
    ui.exportCierresDiariosBtn.addEventListener("click", () => {
      if (typeof roleManager !== "undefined" && roleManager && !roleManager.hasPermission("financial.registros.export")) {
        alert("No tienes permisos para exportar.");
        return;
      }
      const all = loadEntries();
      const cierres = filterAndSortCierresDiarios(all);
      exportCSV(cierres);
    });
  }


  // Event listeners para botones de editar/borrar en las vistas de Ingresos, Gastos y Cierres Diarios
  if (ui.ingresosTbody) {
    ui.ingresosTbody.addEventListener("click", handleTableAction);
  }
  if (ui.gastosTbody) {
    ui.gastosTbody.addEventListener("click", handleTableAction);
  }
  if (ui.cierresDiariosTbody) {
    ui.cierresDiariosTbody.addEventListener("click", handleTableAction);
  }

  // Event listeners para filtros de Ingresos
  if (ui.filterIngresosTienda) {
    ui.filterIngresosTienda.addEventListener("change", renderIngresos);
  }
  if (ui.filterIngresosPeriodo) {
    ui.filterIngresosPeriodo.addEventListener("change", () => {
      if (ui.filterIngresosCustomDates) {
        if (ui.filterIngresosPeriodo.value === "custom") {
          ui.filterIngresosCustomDates.classList.remove("hidden");
          ui.filterIngresosCustomDates.classList.add("grid");
        } else {
          ui.filterIngresosCustomDates.classList.add("hidden");
          ui.filterIngresosCustomDates.classList.remove("grid");
        }
      }
      renderIngresos();
    });
  }
  if (ui.filterIngresosFechaDesde) {
    ui.filterIngresosFechaDesde.addEventListener("change", renderIngresos);
  }
  if (ui.filterIngresosFechaHasta) {
    ui.filterIngresosFechaHasta.addEventListener("change", renderIngresos);
  }
  if (ui.filterIngresosTipo) {
    ui.filterIngresosTipo.addEventListener("change", renderIngresos);
  }
  if (ui.filterIngresosCategoria) {
    ui.filterIngresosCategoria.addEventListener("change", renderIngresos);
  }
  if (ui.filterIngresosUsuario) {
    ui.filterIngresosUsuario.addEventListener("change", renderIngresos);
  }

  // Ordenación por clic en encabezados de Ingresos
  // Usar delegación de eventos en el contenedor de la vista para que funcione siempre
  if (ui.viewIngresos) {
    ui.viewIngresos.addEventListener("click", (e) => {
      // Buscar el th más cercano, incluso si se hace clic en el span
      let th = e.target.closest("th[data-sort]");
      // Si no se encuentra, buscar si el target es un span dentro de un th
      if (!th && e.target.tagName === "SPAN") {
        th = e.target.parentElement.closest("th[data-sort]");
      }
      if (!th || !th.dataset.sort) return;
      
      e.preventDefault();
      e.stopPropagation();
      
      const sortField = th.dataset.sort;
      
      // Determinar la nueva dirección de ordenación
      let newDir;
      if (window.sortIngresosField === sortField) {
        // Misma columna: alternar entre desc y asc
        newDir = window.sortIngresosDir === "desc" ? "asc" : "desc";
      } else {
        // Nueva columna: empezar con desc
        newDir = "desc";
      }
      
      // Actualizar el estado global
      window.sortIngresosField = sortField;
      window.sortIngresosDir = newDir;
      
      // Renderizar (esto actualizará los indicadores)
      renderIngresos();
    });
  }

  // Event listeners para filtros de Gastos
  if (ui.filterGastosTienda) {
    ui.filterGastosTienda.addEventListener("change", renderGastos);
  }
  if (ui.filterGastosPeriodo) {
    ui.filterGastosPeriodo.addEventListener("change", () => {
      if (ui.filterGastosCustomDates) {
        if (ui.filterGastosPeriodo.value === "custom") {
          ui.filterGastosCustomDates.classList.remove("hidden");
          ui.filterGastosCustomDates.classList.add("grid");
        } else {
          ui.filterGastosCustomDates.classList.add("hidden");
          ui.filterGastosCustomDates.classList.remove("grid");
        }
      }
      renderGastos();
    });
  }
  if (ui.filterGastosFechaDesde) {
    ui.filterGastosFechaDesde.addEventListener("change", renderGastos);
  }
  if (ui.filterGastosFechaHasta) {
    ui.filterGastosFechaHasta.addEventListener("change", renderGastos);
  }
  if (ui.filterGastosTipo) {
    ui.filterGastosTipo.addEventListener("change", renderGastos);
  }
  if (ui.filterGastosCategoria) {
    ui.filterGastosCategoria.addEventListener("change", renderGastos);
  }
  if (ui.filterGastosUsuario) {
    ui.filterGastosUsuario.addEventListener("change", renderGastos);
  }

  // Filtros: Cierres Diarios
  if (ui.filterCierresDiariosTienda) {
    ui.filterCierresDiariosTienda.addEventListener("change", renderCierresDiarios);
  }
  if (ui.filterCierresDiariosPeriodo) {
    ui.filterCierresDiariosPeriodo.addEventListener("change", () => {
      if (ui.filterCierresDiariosCustomDates) {
        if (ui.filterCierresDiariosPeriodo.value === "custom") {
          ui.filterCierresDiariosCustomDates.classList.remove("hidden");
          ui.filterCierresDiariosCustomDates.classList.add("grid");
        } else {
          ui.filterCierresDiariosCustomDates.classList.add("hidden");
          ui.filterCierresDiariosCustomDates.classList.remove("grid");
        }
      }
      renderCierresDiarios();
    });
  }
  if (ui.filterCierresDiariosFechaDesde) {
    ui.filterCierresDiariosFechaDesde.addEventListener("change", renderCierresDiarios);
  }
  if (ui.filterCierresDiariosFechaHasta) {
    ui.filterCierresDiariosFechaHasta.addEventListener("change", renderCierresDiarios);
  }
  if (ui.filterCierresDiariosUsuario) {
    ui.filterCierresDiariosUsuario.addEventListener("change", renderCierresDiarios);
  }

  // Ordenación por clic en encabezados de Cierres Diarios
  if (ui.viewCierresDiarios) {
    ui.viewCierresDiarios.addEventListener("click", (e) => {
      let th = e.target.closest("th[data-sort]");
      if (!th && e.target.tagName === "SPAN") {
        th = e.target.parentElement.closest("th[data-sort]");
      }
      if (!th) return;
      const field = th.dataset.sort;
      if (!field) return;
      const currentDir = th.dataset.sortDir || "";
      const newDir = currentDir === "desc" ? "asc" : "desc";
      window.sortCierresDiariosField = field;
      window.sortCierresDiariosDir = newDir;
      renderCierresDiarios();
    });
  }

  // Ordenación por clic en encabezados de Gastos
  // Usar delegación de eventos en el contenedor de la vista para que funcione siempre
  if (ui.viewGastos) {
    ui.viewGastos.addEventListener("click", (e) => {
      // Buscar el th más cercano, incluso si se hace clic en el span
      let th = e.target.closest("th[data-sort]");
      // Si no se encuentra, buscar si el target es un span dentro de un th
      if (!th && e.target.tagName === "SPAN") {
        th = e.target.parentElement.closest("th[data-sort]");
      }
      if (!th || !th.dataset.sort) return;
      
      e.preventDefault();
      e.stopPropagation();
      
      const sortField = th.dataset.sort;
      
      // Determinar la nueva dirección de ordenación
      let newDir;
      if (window.sortGastosField === sortField) {
        // Misma columna: alternar entre desc y asc
        newDir = window.sortGastosDir === "desc" ? "asc" : "desc";
      } else {
        // Nueva columna: empezar con desc
        newDir = "desc";
      }
      
      // Actualizar el estado global
      window.sortGastosField = sortField;
      window.sortGastosDir = newDir;
      
      // Renderizar (esto actualizará los indicadores)
      renderGastos();
    });
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !ui.entryModal.classList.contains("hidden")) closeModal();
  });
}

function handleTableAction(e) {
  const btn = e.target.closest("button");
  if (!btn) return;
  const action = btn.dataset.action;
  const id = btn.dataset.id;
  if (!action || !id) return;

  if (action === "edit") {
    const entry = findEntry(id);
    if (!entry) return;
    openModal("edit", entry);
    return;
  }

  if (action === "delete") {
    const entry = findEntry(id);
    if (!entry) return;
    const ok = confirm(`¿Borrar el registro del ${entry.date} (${storeName(entry.storeId)})?`);
    if (!ok) return;
    deleteEntry(id);
    // Refrescar la vista actual
    if (ui.viewIngresos && !ui.viewIngresos.classList.contains("hidden")) {
      renderIngresos();
    } else if (ui.viewGastos && !ui.viewGastos.classList.contains("hidden")) {
      renderGastos();
    } else if (ui.viewCierresDiarios && !ui.viewCierresDiarios.classList.contains("hidden")) {
      renderCierresDiarios();
    } else {
      render();
    }
  }
}

/** -------- Init -------- */

function initDashboard() {
  maybeSeedDemoData();
  cleanTrash(); // Limpiar papelera automáticamente al iniciar
  
  // Cargar y refrescar tiendas desde companyManager
  refreshAllStoreSelects();
  
  const availableStores = getAvailableStores();
  const assignedStores = typeof authManager !== "undefined" && authManager ? authManager.getAssignedStores() : null;
  const hasAssignedStores = assignedStores && (Array.isArray(assignedStores) ? assignedStores.length > 0 : true);

  // Selector superior: si el usuario tiene tienda asignada, no mostramos "Todas (empresa)"
  setSelectOptions(ui.storeSelect, availableStores, !hasAssignedStores);
  setSelectOptions(ui.formStore, availableStores, false);

  if (hasAssignedStores && availableStores.length === 1) {
    ui.storeSelect.value = availableStores[0].id;
    ui.storeSelect.disabled = true;
    ui.formStore.value = availableStores[0].id;
    ui.formStore.disabled = true;
  } else {
    ui.storeSelect.value = "ALL";
    ui.storeSelect.disabled = false;
  }

  ui.periodSelect.value = state.period;
  ui.formDate.value = todayISO();

  wireEvents();
  render();
}

function showLoginScreen() {
  const loginScreen = document.getElementById("loginScreen");
  const mainContent = document.getElementById("mainContent");
  if (loginScreen) loginScreen.classList.remove("hidden");
  if (mainContent) mainContent.classList.add("hidden");
}

function showMainContent() {
  const loginScreen = document.getElementById("loginScreen");
  const mainContent = document.getElementById("mainContent");
  if (loginScreen) loginScreen.classList.add("hidden");
  if (mainContent) mainContent.classList.remove("hidden");
}

function updateUserInfo() {
  const userInfo = document.getElementById("currentUserInfo");
  if (!userInfo) return;
  if (typeof authManager === "undefined" || !authManager || !authManager.isAuthenticated()) {
    userInfo.textContent = "";
    return;
  }
  const user = authManager.getCurrentUser();
  const roleLabel = typeof ROLES !== "undefined" && ROLES[user.role] ? ROLES[user.role].name : user.role;
  const assignedStores = user.assignedStores || (user.assignedStore ? [user.assignedStore] : null);
  const storeLabel = assignedStores 
    ? (Array.isArray(assignedStores) 
      ? assignedStores.map(s => STORES.find(store => store.id === s)?.name || s).join(', ')
      : STORES.find((s) => s.id === assignedStores)?.name || assignedStores)
    : "Todas las tiendas";

  userInfo.innerHTML = `
    <div class="font-semibold text-slate-900">${escapeHtml(user.name || user.username)}</div>
    <div class="mt-1 text-slate-600">${escapeHtml(roleLabel)}</div>
    <div class="mt-1 text-xs text-slate-500">${escapeHtml(storeLabel)}</div>
  `;
}

function wireLogout() {
  const logoutBtn = document.getElementById("logoutBtn");
  if (!logoutBtn) return;
  logoutBtn.onclick = () => {
    if (typeof authManager !== "undefined" && authManager) authManager.logout();
    showLoginScreen();
  };
}

function initializeLogin() {
  const loginForm = document.getElementById("loginForm");
  if (!loginForm) {
    console.error("Formulario de login no encontrado");
    return;
  }

  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const usernameInput = document.getElementById("loginUsername");
    const passwordInput = document.getElementById("loginPassword");
    const errorDiv = document.getElementById("loginError");

    const username = usernameInput ? (usernameInput.value || "").trim() : "";
    const password = passwordInput ? passwordInput.value || "" : "";

    // Limpiar error previo
    if (errorDiv) {
      errorDiv.classList.add("hidden");
      errorDiv.textContent = "";
    }

    // Validar que se hayan ingresado datos
    if (!username) {
      if (errorDiv) {
        errorDiv.textContent = "Por favor, ingresa tu nombre de usuario.";
        errorDiv.classList.remove("hidden");
      }
      if (usernameInput) usernameInput.focus();
      return;
    }

    if (!password) {
      if (errorDiv) {
        errorDiv.textContent = "Por favor, ingresa tu contraseña.";
        errorDiv.classList.remove("hidden");
      }
      if (passwordInput) passwordInput.focus();
      return;
    }

    // Verificar que authManager esté disponible
    if (typeof authManager === "undefined" || !authManager) {
      console.error("authManager no está disponible");
      if (errorDiv) {
        errorDiv.textContent = "Error: sistema de autenticación no disponible. Por favor, recarga la página.";
        errorDiv.classList.remove("hidden");
      }
      return;
    }

    // Intentar login
    try {
      const result = authManager.login(username, password);
      if (!result.success) {
        if (errorDiv) {
          errorDiv.textContent = result.message || "No se pudo iniciar sesión. Verifica tus credenciales.";
          errorDiv.classList.remove("hidden");
        }
        // Limpiar el campo de contraseña
        if (passwordInput) passwordInput.value = "";
        if (passwordInput) passwordInput.focus();
        return;
      }

      // Login exitoso
      if (errorDiv) errorDiv.classList.add("hidden");
      startAuthenticatedApp();
    } catch (error) {
      console.error("Error durante el login:", error);
      if (errorDiv) {
        errorDiv.textContent = "Error inesperado durante el inicio de sesión. Por favor, intenta de nuevo.";
        errorDiv.classList.remove("hidden");
      }
    }
  });
}

function startAuthenticatedApp() {
  showMainContent();
  updateUserInfo();
  wireLogout();

  // Roles / permisos
  if (typeof RoleManager !== "undefined") {
    // roles.js declara `let roleManager` global
    roleManager = new RoleManager();
    if (roleManager && typeof roleManager.applyRolePermissions === "function") {
      setTimeout(() => roleManager.applyRolePermissions(), 0);
    }
  }

  // Panel de usuarios (solo admin)
  if (
    typeof UserManager !== "undefined" &&
    typeof authManager !== "undefined" &&
    authManager &&
    authManager.hasPermission("admin.users.view")
  ) {
    userManager = new UserManager();
  }

  // Gestor de datos de empresa
  if (typeof CompanyManager !== "undefined") {
    companyManager = new CompanyManager();
    // Cargar tiendas desde companyManager
    loadStoresFromCompany();
  }

  // Gestor de empleados
  if (typeof EmployeeManager !== "undefined") {
    employeeManager = new EmployeeManager();
  }

  // Gestor de pedidos
  if (typeof OrderManager !== "undefined") {
    orderManager = new OrderManager();
    // Asegurar que el enlace se cree después de que todo esté cargado
    setTimeout(() => {
      if (orderManager && typeof orderManager.createMenuLink === "function") {
        orderManager.createMenuLink();
      }
    }, 500);
  }

  initDashboard();
  // Mostrar dashboard por defecto
  showView("dashboard");
}

function boot() {
  // authManager se crea en auth.js en DOMContentLoaded; esperamos si aún no está listo
  if (typeof authManager === "undefined" || !authManager) {
    console.log("Esperando a que authManager esté disponible...");
    setTimeout(boot, 100);
    return;
  }

  console.log("authManager inicializado correctamente");
  console.log("Usuarios disponibles:", authManager.users ? authManager.users.map(u => u.username) : "ninguno");
  
  initializeLogin();
  
  if (authManager.isAuthenticated()) {
    console.log("Usuario ya autenticado");
    startAuthenticatedApp();
  } else {
    console.log("No hay usuario autenticado, mostrando pantalla de login");
    showLoginScreen();
  }
}

// Esperar a que el DOM esté completamente cargado
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM cargado, iniciando boot...");
    boot();
  });
} else {
  console.log("DOM ya cargado, iniciando boot...");
  boot();
}

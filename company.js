// Gestión de datos de la empresa
const COMPANY_STORAGE_KEY = 'miramira_company_data';

class CompanyManager {
  constructor() {
    this.data = this.loadCompanyData();
    this.isEditingCompany = false;
    this.editingBusinessIndex = null; // null o el índice del negocio que se está editando
    this.originalData = null;
    this.originalBusinessData = null;
    this.initializeUI();
  }

  loadCompanyData() {
    const stored = localStorage.getItem(COMPANY_STORAGE_KEY);
    let data = null;
    if (stored) {
      try {
        data = JSON.parse(stored);
      } catch (e) {
        console.error('Error cargando datos de empresa:', e);
      }
    }
    
    // Si no hay datos, usar los por defecto
    if (!data) {
      data = {
        name: 'Miramira',
        cif: '',
        fiscalStreet: '',
        fiscalPostalCode: '',
        fiscalEmail: '',
        businesses: [
          { id: 'luz_del_tajo', name: 'Miramira - Luz del Tajo', street: '', postalCode: '', email: '' },
          { id: 'maquinista', name: 'Miramira - Maquinista', street: '', postalCode: '', email: '' },
          { id: 'puerto_venecia', name: 'Miramira - Puerto Venecia', street: '', postalCode: '', email: '' },
          { id: 'xanadu', name: 'Miramira - Xanadu', street: '', postalCode: '', email: '' }
        ]
      };
    }
    
    // Migración: convertir fiscalAddress antigua a street y postalCode
    if (data.fiscalAddress && !data.fiscalStreet) {
      data.fiscalStreet = data.fiscalAddress;
      data.fiscalPostalCode = '';
      delete data.fiscalAddress;
    }
    
    // Migración: convertir address antigua de negocios a street y postalCode
    if (data.businesses) {
      data.businesses = data.businesses.map(business => {
        if (business.address && !business.street) {
          business.street = business.address;
          business.postalCode = '';
          delete business.address;
        }
        // Asegurar que existan los campos
        if (!business.street) business.street = '';
        if (!business.postalCode) business.postalCode = '';
        if (!business.city) business.city = '';
        if (!business.email) business.email = '';
        return business;
      });
    }
    
    // Asegurar que existan los campos de dirección fiscal
    if (!data.fiscalStreet) data.fiscalStreet = '';
    if (!data.fiscalPostalCode) data.fiscalPostalCode = '';
    if (!data.fiscalCity) data.fiscalCity = '';
    if (!data.fiscalEmail) data.fiscalEmail = '';
    
    // Migración: si hay email en otro campo, moverlo
    if (data.email && !data.fiscalEmail) {
      data.fiscalEmail = data.email;
      delete data.email;
    }
    
    return data;
  }

  saveCompanyData() {
    localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(this.data));
  }

  initializeUI() {
    // Crear enlace en el menú del sidebar
    this.createMenuLink();
  }

  createMenuLink() {
    const sidebar = document.querySelector('aside nav');
    if (!sidebar) return;

    // Verificar si ya existe
    if (document.getElementById('companyMenuLink')) return;

    const companyLink = document.createElement('a');
    companyLink.id = 'companyMenuLink';
    companyLink.href = '#';
    companyLink.className = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50';
    companyLink.innerHTML = `
      <span class="text-slate-500" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9 22V12h6v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      Datos de la empresa
    `;

    companyLink.addEventListener('click', (e) => {
      e.preventDefault();
      this.showCompanyView();
    });

    sidebar.appendChild(companyLink);
  }

  showCompanyView() {
    const main = document.querySelector('main');
    if (!main) return;

    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('admin.company.edit');
    this.isEditingCompany = false;
    this.editingBusinessIndex = null;
    this.originalData = JSON.parse(JSON.stringify(this.data)); // Backup de datos originales

    main.innerHTML = `
      <div class="space-y-6">
        <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-lg font-semibold">Datos de la empresa</h1>
              <p class="text-sm text-slate-500">Información fiscal y datos de los negocios</p>
            </div>
          </div>
        </header>

        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
          <form id="companyForm" class="space-y-6">
            <!-- Datos de la empresa -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900">Información fiscal</h2>
                ${canEdit ? `
                  <div id="companyInfoActions">
                    <button
                      type="button"
                      id="editCompanyBtn"
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Editar
                    </button>
                    <div id="companyInfoSaveCancel" class="hidden flex items-center gap-2">
                      <button
                        type="button"
                        id="saveCompanyInfoBtn"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                      >
                        Guardar
                      </button>
                      <button
                        type="button"
                        id="cancelCompanyInfoBtn"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                      >
                        Cancelar
                      </button>
                    </div>
                  </div>
                ` : ''}
              </div>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                  <span class="text-xs font-semibold text-slate-700">Nombre de la empresa</span>
                  <input
                    id="companyName"
                    type="text"
                    value="${this.escapeHtml(this.data.name || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>

                <label class="block">
                  <span class="text-xs font-semibold text-slate-700">CIF</span>
                  <input
                    id="companyCif"
                    type="text"
                    value="${this.escapeHtml(this.data.cif || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>

                <label class="block md:col-span-2">
                  <span class="text-xs font-semibold text-slate-700">Calle</span>
                  <input
                    id="companyStreet"
                    type="text"
                    value="${this.escapeHtml(this.data.fiscalStreet || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>
                <label class="block">
                  <span class="text-xs font-semibold text-slate-700">Código postal</span>
                  <input
                    id="companyPostalCode"
                    type="text"
                    value="${this.escapeHtml(this.data.fiscalPostalCode || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>
                <label class="block">
                  <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                  <input
                    id="companyCity"
                    type="text"
                    value="${this.escapeHtml(this.data.fiscalCity || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>
                <label class="block md:col-span-2">
                  <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                  <input
                    id="companyEmail"
                    type="email"
                    value="${this.escapeHtml(this.data.fiscalEmail || '')}"
                    readonly
                    disabled
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"
                  />
                </label>
              </div>
            </div>

            <!-- Negocios -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900">Negocios</h2>
                ${canEdit ? `
                  <button
                    type="button"
                    id="addBusinessBtn"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir negocio
                  </button>
                ` : ''}
              </div>

              <div id="businessesContainer" class="space-y-4">
                <!-- Los negocios se renderizan aquí -->
              </div>
            </div>

          </form>
        </div>
      </div>
    `;

    this.renderBusinesses();
    this.initializeEventListeners();
    // Asegurar que el estado inicial sea correcto - usar setTimeout para asegurar que el DOM esté listo
    setTimeout(() => {
      this.updateEditMode();
      // Verificación adicional: forzar deshabilitación de todos los campos
      const allInputs = document.querySelectorAll('#companyForm input, #companyForm textarea');
      allInputs.forEach(input => {
        if (input.id && input.id.startsWith('company')) {
          // Es un campo de información fiscal
          if (!this.isEditingCompany) {
            input.setAttribute('readonly', 'readonly');
            input.setAttribute('disabled', 'disabled');
            input.readOnly = true;
            input.disabled = true;
          }
        } else if (input.hasAttribute('data-business-index')) {
          // Es un campo de negocio
          const index = parseInt(input.getAttribute('data-business-index'));
          if (this.editingBusinessIndex !== index) {
            input.setAttribute('readonly', 'readonly');
            input.setAttribute('disabled', 'disabled');
            input.readOnly = true;
            input.disabled = true;
          }
        }
      });
    }, 100);
  }

  renderBusinesses() {
    const container = document.getElementById('businessesContainer');
    if (!container) return;

    if (this.data.businesses.length === 0) {
      container.innerHTML = `
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-500">
          No hay negocios registrados.
        </div>
      `;
      return;
    }

    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('admin.company.edit');
    let html = '';
    this.data.businesses.forEach((business, index) => {
      const isEditing = this.editingBusinessIndex === index;
      html += `
        <div class="rounded-xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900">${this.escapeHtml(business.name || 'Negocio sin nombre')}</h3>
            ${canEdit ? `
              <div id="businessActions_${index}">
                ${!isEditing ? `
                  <button
                    type="button"
                    data-business-index="${index}"
                    data-action="edit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Editar
                  </button>
                ` : `
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      data-business-index="${index}"
                      data-action="save"
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                    >
                      Guardar
                    </button>
                    <button
                      type="button"
                      data-business-index="${index}"
                      data-action="cancel"
                      class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                      Cancelar
                    </button>
                    <button
                      type="button"
                      data-business-index="${index}"
                      data-action="remove"
                      class="inline-flex items-center justify-center gap-2 rounded-lg p-2 text-rose-600 hover:bg-rose-50"
                      title="Eliminar negocio"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                `}
              </div>
            ` : ''}
          </div>
          <div class="space-y-3">
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Nombre del negocio</span>
              <input
                type="text"
                data-business-index="${index}"
                data-field="name"
                value="${this.escapeHtml(business.name || '')}"
                ${isEditing ? '' : 'readonly disabled'}
                class="mt-1 w-full rounded-xl border border-slate-200 ${isEditing ? 'bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4' : 'bg-slate-50 px-3 py-2 text-sm outline-none'}"
              />
            </label>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[2fr_1fr]">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Calle</span>
                <input
                  type="text"
                  data-business-index="${index}"
                  data-field="street"
                  value="${this.escapeHtml(business.street || '')}"
                  ${isEditing ? '' : 'readonly disabled'}
                  class="mt-1 w-full rounded-xl border border-slate-200 ${isEditing ? 'bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4' : 'bg-slate-50 px-3 py-2 text-sm outline-none'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Código postal</span>
                <input
                  type="text"
                  data-business-index="${index}"
                  data-field="postalCode"
                  value="${this.escapeHtml(business.postalCode || '')}"
                  ${isEditing ? '' : 'readonly disabled'}
                  class="mt-1 w-full rounded-xl border border-slate-200 ${isEditing ? 'bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4' : 'bg-slate-50 px-3 py-2 text-sm outline-none'}"
                />
              </label>
            </div>
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Ciudad</span>
              <input
                type="text"
                data-business-index="${index}"
                data-field="city"
                value="${this.escapeHtml(business.city || '')}"
                ${isEditing ? '' : 'readonly disabled'}
                class="mt-1 w-full rounded-xl border border-slate-200 ${isEditing ? 'bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4' : 'bg-slate-50 px-3 py-2 text-sm outline-none'}"
              />
            </label>
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
              <input
                type="email"
                data-business-index="${index}"
                data-field="email"
                value="${this.escapeHtml(business.email || '')}"
                ${isEditing ? '' : 'readonly disabled'}
                class="mt-1 w-full rounded-xl border border-slate-200 ${isEditing ? 'bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4' : 'bg-slate-50 px-3 py-2 text-sm outline-none'}"
              />
            </label>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
    this.updateEditMode();
  }

  updateEditMode() {
    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('admin.company.edit');

    // Asegurar que el estado inicial sea correcto
    if (this.isEditingCompany === undefined) {
      this.isEditingCompany = false;
    }
    if (this.editingBusinessIndex === undefined) {
      this.editingBusinessIndex = null;
    }

    // Actualizar campos de información fiscal
    const nameInput = document.getElementById('companyName');
    const cifInput = document.getElementById('companyCif');
    const streetInput = document.getElementById('companyStreet');
    const postalCodeInput = document.getElementById('companyPostalCode');
    const cityInput = document.getElementById('companyCity');
    const emailInput = document.getElementById('companyEmail');

    [nameInput, cifInput, streetInput, postalCodeInput, cityInput, emailInput].forEach(input => {
      if (input) {
        const shouldBeEditable = this.isEditingCompany === true;
        
        // Forzar los atributos
        if (shouldBeEditable) {
          input.removeAttribute('readonly');
          input.removeAttribute('disabled');
          input.readOnly = false;
          input.disabled = false;
        } else {
          input.setAttribute('readonly', 'readonly');
          input.setAttribute('disabled', 'disabled');
          input.readOnly = true;
          input.disabled = true;
        }
        
        input.className = shouldBeEditable
          ? 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4'
          : 'mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none';
      }
    });

    // Mostrar/ocultar botones de información fiscal
    const editCompanyBtn = document.getElementById('editCompanyBtn');
    const companyInfoSaveCancel = document.getElementById('companyInfoSaveCancel');
    if (editCompanyBtn) {
      editCompanyBtn.classList.toggle('hidden', this.isEditingCompany);
    }
    if (companyInfoSaveCancel) {
      companyInfoSaveCancel.classList.toggle('hidden', !this.isEditingCompany);
    }

    // Actualizar campos de negocios - solo el que se está editando
    const container = document.getElementById('businessesContainer');
    if (container) {
      container.querySelectorAll('input[data-business-index], textarea[data-business-index]').forEach(input => {
        const index = parseInt(input.getAttribute('data-business-index'));
        const isEditing = this.editingBusinessIndex === index;
        
        // Forzar los atributos
        if (isEditing) {
          input.removeAttribute('readonly');
          input.removeAttribute('disabled');
          input.readOnly = false;
          input.disabled = false;
        } else {
          input.setAttribute('readonly', 'readonly');
          input.setAttribute('disabled', 'disabled');
          input.readOnly = true;
          input.disabled = true;
        }
        
        const baseClass = input.tagName === 'TEXTAREA' 
          ? 'mt-1 w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none'
          : 'mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none';
        input.className = isEditing
          ? baseClass + ' bg-white ring-brand-200 focus:ring-4'
          : baseClass + ' bg-slate-50';
      });
    }
  }

  initializeEventListeners() {
    // Botón editar información fiscal
    const editCompanyBtn = document.getElementById('editCompanyBtn');
    if (editCompanyBtn) {
      editCompanyBtn.addEventListener('click', () => {
        this.enterEditCompanyMode();
      });
    }

    // Botón guardar información fiscal
    const saveCompanyInfoBtn = document.getElementById('saveCompanyInfoBtn');
    if (saveCompanyInfoBtn) {
      saveCompanyInfoBtn.addEventListener('click', () => {
        this.saveCompanyInfo();
      });
    }

    // Botón cancelar información fiscal
    const cancelCompanyInfoBtn = document.getElementById('cancelCompanyInfoBtn');
    if (cancelCompanyInfoBtn) {
      cancelCompanyInfoBtn.addEventListener('click', () => {
        this.cancelEditCompany();
      });
    }

    // Botón añadir negocio
    const addBusinessBtn = document.getElementById('addBusinessBtn');
    if (addBusinessBtn) {
      addBusinessBtn.addEventListener('click', () => {
        this.data.businesses.push({
          id: 'business-' + Date.now(),
          name: '',
          street: '',
          postalCode: '',
          city: '',
          email: ''
        });
        // Iniciar en modo edición para el nuevo negocio
        this.editingBusinessIndex = this.data.businesses.length - 1;
        this.originalBusinessData = JSON.parse(JSON.stringify(this.data.businesses[this.editingBusinessIndex]));
        this.renderBusinesses();
      });
    }

    // Event listeners para acciones de negocios (editar, guardar, cancelar, eliminar)
    const container = document.getElementById('businessesContainer');
    if (container) {
      container.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        
        const action = btn.getAttribute('data-action');
        const index = parseInt(btn.getAttribute('data-business-index'));
        
        if (action === 'edit') {
          this.enterEditBusinessMode(index);
        } else if (action === 'save') {
          this.saveBusiness(index);
        } else if (action === 'cancel') {
          this.cancelEditBusiness(index);
        } else if (action === 'remove') {
          if (confirm('¿Estás seguro de que quieres eliminar este negocio?')) {
            this.data.businesses.splice(index, 1);
            if (this.editingBusinessIndex === index) {
              this.editingBusinessIndex = null;
              this.originalBusinessData = null;
            }
            this.renderBusinesses();
          }
        }
      });
    }
  }

  enterEditCompanyMode() {
    this.isEditingCompany = true;
    this.originalData = JSON.parse(JSON.stringify({
      name: this.data.name,
      cif: this.data.cif,
      fiscalStreet: this.data.fiscalStreet,
      fiscalPostalCode: this.data.fiscalPostalCode,
      fiscalCity: this.data.fiscalCity,
      fiscalEmail: this.data.fiscalEmail
    }));
    this.updateEditMode();
  }

  cancelEditCompany() {
    // Restaurar datos originales de información fiscal
    if (this.originalData) {
      this.data.name = this.originalData.name || '';
      this.data.cif = this.originalData.cif || '';
      this.data.fiscalStreet = this.originalData.fiscalStreet || '';
      this.data.fiscalPostalCode = this.originalData.fiscalPostalCode || '';
      this.data.fiscalCity = this.originalData.fiscalCity || '';
      this.data.fiscalEmail = this.originalData.fiscalEmail || '';
    }
    
    this.isEditingCompany = false;
    
    // Restaurar valores en los campos
    const nameInput = document.getElementById('companyName');
    const cifInput = document.getElementById('companyCif');
    const streetInput = document.getElementById('companyStreet');
    const postalCodeInput = document.getElementById('companyPostalCode');
    const cityInput = document.getElementById('companyCity');
    const emailInput = document.getElementById('companyEmail');
    
    if (nameInput) nameInput.value = this.data.name || '';
    if (cifInput) cifInput.value = this.data.cif || '';
    if (streetInput) streetInput.value = this.data.fiscalStreet || '';
    if (postalCodeInput) postalCodeInput.value = this.data.fiscalPostalCode || '';
    if (cityInput) cityInput.value = this.data.fiscalCity || '';
    if (emailInput) emailInput.value = this.data.fiscalEmail || '';
    
    this.updateEditMode();
  }

  saveCompanyInfo() {
    // Recopilar datos del formulario
    const nameInput = document.getElementById('companyName');
    const cifInput = document.getElementById('companyCif');
    const streetInput = document.getElementById('companyStreet');
    const postalCodeInput = document.getElementById('companyPostalCode');
    const cityInput = document.getElementById('companyCity');
    const emailInput = document.getElementById('companyEmail');

    if (nameInput) this.data.name = nameInput.value.trim();
    if (cifInput) this.data.cif = cifInput.value.trim();
    if (streetInput) this.data.fiscalStreet = streetInput.value.trim();
    if (postalCodeInput) this.data.fiscalPostalCode = postalCodeInput.value.trim();
    if (cityInput) this.data.fiscalCity = cityInput.value.trim();
    if (emailInput) this.data.fiscalEmail = emailInput.value.trim();

    // Guardar
    this.saveCompanyData();
    this.isEditingCompany = false;
    this.originalData = null;
    alert('La información fiscal se ha guardado correctamente.');
    
    // Actualizar la vista
    this.updateEditMode();
  }

  enterEditBusinessMode(index) {
    this.editingBusinessIndex = index;
    this.originalBusinessData = JSON.parse(JSON.stringify(this.data.businesses[index]));
    this.renderBusinesses();
  }

  cancelEditBusiness(index) {
    // Restaurar datos originales del negocio
    if (this.originalBusinessData && this.data.businesses[index]) {
      this.data.businesses[index] = JSON.parse(JSON.stringify(this.originalBusinessData));
    }
    
    this.editingBusinessIndex = null;
    this.originalBusinessData = null;
    this.renderBusinesses();
  }

  saveBusiness(index) {
    if (index < 0 || index >= this.data.businesses.length) return;

    // Recopilar datos del negocio desde los inputs
    const container = document.getElementById('businessesContainer');
    if (container) {
      container.querySelectorAll(`input[data-business-index="${index}"], textarea[data-business-index="${index}"]`).forEach(input => {
        const field = input.getAttribute('data-field');
        if (field) {
          this.data.businesses[index][field] = input.value.trim();
        }
      });
    }

    // Validar que tenga nombre
    if (!this.data.businesses[index].name || !this.data.businesses[index].name.trim()) {
      alert('El negocio debe tener un nombre.');
      return;
    }

    // Guardar
    this.saveCompanyData();
    this.editingBusinessIndex = null;
    this.originalBusinessData = null;
    alert('El negocio se ha guardado correctamente.');
    
    // Actualizar la vista
    this.renderBusinesses();
    
    // Actualizar todos los selects de tiendas en la aplicación
    if (typeof refreshAllStoreSelects === 'function') {
      refreshAllStoreSelects();
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  getCompanyData() {
    return this.data;
  }
}

// Instancia global del gestor de empresa
let companyManager;

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof authManager !== 'undefined' && authManager && authManager.isAuthenticated()) {
      companyManager = new CompanyManager();
    }
  });
} else {
  if (typeof authManager !== 'undefined' && authManager && authManager.isAuthenticated()) {
    companyManager = new CompanyManager();
  }
}

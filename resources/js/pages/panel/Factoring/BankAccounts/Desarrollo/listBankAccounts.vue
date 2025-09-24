<template>
  <DataTable :lazy="true" ref="dt" v-model:selection="selectedAccounts" :value="accounts" dataKey="id" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="first" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuentas" @page="onPage" scrollable
    scrollHeight="574px" class="p-datatable-sm" :sortField="sortField" :sortOrder="sortOrder" :sortMode="'single'"
    @sort="onSort">

    <!-- Header -->
    <template #header>
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <h4 class="m-0">
          Cuentas Bancarias
          <Tag severity="contrast" :value="totalRecords" />
        </h4>
        <div class="flex flex-wrap gap-2">
          <IconField>
            <InputIcon><i class="pi pi-search" /></InputIcon>
            <InputText v-model="globalFilter" @input="onGlobalSearch" placeholder="Buscar..." />
          </IconField>
          <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" severity="contrast"
            @click="loadAccounts" />
        </div>
      </div>
    </template>

    <!-- Columns -->
    <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
    <Column field="inversionista" header="Inversionista" sortable style="min-width: 18rem" />
    <Column field="banco" header="Banco" sortable style="min-width: 15rem" />
    <Column field="type" header="Tipo" sortable style="min-width: 10rem" />
    <Column field="currency" header="Moneda" sortable style="min-width: 8rem" />
    <Column field="cc" header="Cuenta" sortable style="min-width: 12rem" />
    <Column field="cci" header="CCI" sortable style="min-width: 15rem" />

    <!-- Estado 0 -->
    <Column field="estado0" header="1º Aprobador" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.estado0" :severity="getStatus0Severity(data.estado0)" />
      </template>
    </Column>

    <Column field="updated0_by_name" header="1º user Apr." sortable style="min-width: 8rem" />

    <Column header="Tº 1ª Aprobación" style="min-width: 15rem" sortable>
      <template #body="{ data }">{{ data.updated0_at ?? '—' }}</template>
    </Column>

    <!-- Estado principal -->
    <Column field="estado" header="2º Aprobador" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.estado" :severity="getStatusSeverity(data.estado)" />
      </template>
    </Column>

    <Column field="updated_by_name" header="2º user Apr." sortable style="min-width: 8rem" />
    <Column header="Tº 2ª Aprobación" sortable style="min-width: 15rem">
      <template #body="{ data }">{{ data.updated_last_at ?? '—' }}</template>
    </Column>

    <!-- Estado de Conclusión -->
    <Column field="estado_conclusion" header="Estado Conclusión" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.can_show_conclusion ? (data.estado_conclusion ?? '—') : 'N/A'"
          :severity="data.can_show_conclusion ? getConclusionSeverity(data.estado_conclusion) : 'secondary'" />
      </template>
    </Column>

    <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />

    <!-- Acciones -->
    <Column header="" style="min-width: 7rem; text-align:center">
      <template #body="{ data }">
        <div class="flex items-center gap-2 justify-center">
          <Button v-if="data.can_show_conclusion" icon="pi pi-eye" text rounded @click="openShowModal(data)" />
          <span v-else class="text-xs text-gray-400">N/A</span>
        </div>
      </template>
    </Column>
  </DataTable>

  <!-- Modal de Detalle -->
  <Dialog v-model:visible="showDialog" :style="{ width: '960px' }" header="Detalle de Cuenta Bancaria" :modal="true">
    <div v-if="selectedAccount" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <p class="text-xs text-gray-500">Inversionista</p>
        <p class="font-semibold">{{ selectedAccount.inversionista }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Banco</p>
        <p class="font-semibold">{{ selectedAccount.banco }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Tipo</p>
        <p class="font-semibold">{{ selectedAccount.type }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Moneda</p>
        <p class="font-semibold">{{ selectedAccount.currency }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Cuenta (CC)</p>
        <p class="font-semibold break-all">{{ selectedAccount.cc }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">CCI</p>
        <p class="font-semibold break-all">{{ selectedAccount.cci }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Alias</p>
        <p class="font-semibold">{{ selectedAccount.alias || '—' }}</p>
      </div>
      <div class="flex items-center gap-4">
        <div>
          <p class="text-xs text-gray-500">Estado 0</p>
          <Tag :value="selectedAccount.estado0" :severity="getStatus0Severity(selectedAccount.estado0)" />
        </div>
        <div>
          <p class="text-xs text-gray-500">Estado</p>
          <Tag :value="selectedAccount.estado" :severity="getStatusSeverity(selectedAccount.estado)" />
        </div>
      </div>

      <div>
        <p class="text-xs text-gray-500">Creación</p>
        <p class="font-semibold">{{ selectedAccount.creacion }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Actualización (modelo)</p>
        <p class="font-semibold">{{ formatDateTime(selectedAccount.updated_at ?? selectedAccount.update) }}</p>
      </div>

      <!-- ADJUNTOS -->
      <div class="sm:col-span-2 mt-2">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Adjuntos</p>

        <div v-if="!isFirstApproved">
          <FileUpload name="files[]" :customUpload="true" multiple :auto="true" accept=".pdf,image/*"
            :maxFileSize="10485760" chooseLabel="Seleccionar" :showUploadButton="false" :showCancelButton="false"
            @uploader="onAutoUpload" />

          <div class="mt-3">
            <div class="p-3 rounded border border-gray-200/60">
              <p class="text-xs font-semibold text-gray-500 mb-1">Subidos</p>
              <ul class="text-sm space-y-1">
                <li v-for="(f, i) in uploadedFiles" :key="f.id ?? i" class="flex items-center justify-between gap-3">
                  <span class="truncate">{{ f.original_name ?? f.name }}</span>
                  <Button icon="pi pi-download" label="Descargar" size="small" text @click="downloadAttachment(f)" />
                </li>
                <li v-if="uploadedFiles.length === 0" class="text-gray-500">—</li>
              </ul>
            </div>
          </div>
        </div>

        <div v-else class="mt-1">
          <div class="p-3 rounded border border-gray-200/60">
            <p class="text-xs font-semibold text-gray-500 mb-1">Subidos</p>
            <ul class="text-sm space-y-1">
              <li v-for="(f, i) in uploadedFiles" :key="f.id ?? i" class="flex items-center justify-between gap-3">
                <span class="truncate">{{ f.original_name ?? f.name }}</span>
                <Button icon="pi pi-download" label="Descargar" size="small" text @click="downloadAttachment(f)" />
              </li>
              <li v-if="uploadedFiles.length === 0" class="text-gray-500">—</li>
            </ul>
          </div>
        </div>

        <!-- Previsualizaciones (contenidas con botón ojo) -->
        <div v-if="imageFiles.length" class="mt-4">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Previsualizaciones</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div v-for="(img, idx) in imageFiles" :key="img.id ?? img.url ?? idx"
              class="relative group border rounded-lg bg-gray-50">
              <!-- Caja de contención fija -->
              <div class="w-full h-40 flex items-center justify-center overflow-hidden">
                <img :src="img.url" :alt="img.original_name ?? img.name ?? 'imagen'"
                  class="max-h-full max-w-full object-contain" loading="lazy" />
              </div>

              <!-- Overlay suave al hover -->
              <div
                class="absolute inset-0 rounded-lg bg-black/0 group-hover:bg-black/15 transition-colors duration-150">
              </div>

              <!-- Botón ojo -->
              <Button icon="pi pi-eye" class="!absolute right-2 bottom-2" rounded @click="openImageModal(img.url)"
                v-tooltip.bottom="'Ver en modal'" />

              <!-- Pie con nombre, truncado -->
              <div class="px-2 py-1 text-xs truncate bg-white/90 border-t border-gray-200 rounded-b-lg">
                {{ img.original_name ?? img.name }}
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- COMENTARIOS -->
      <div class="sm:col-span-2 mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="p-3 rounded border border-amber-200">
          <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">
            Comentario (Primera Validación)
          </p>
          <Textarea v-model="comment0" autoResize rows="3" class="w-full" placeholder="Escribe un comentario..."
            :readonly="isFirstApproved" />
          <small class="text-gray-500">
            Se guarda al confirmar la Primera Validación
            <template v-if="selectedAccount.comment0"> • Último guardado: {{ selectedAccount.comment0 }}</template>
          </small>
        </div>

        <div class="p-3 rounded border border-indigo-200">
          <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide mb-1">
            Comentario (Segunda Validación)
          </p>

          <Textarea v-model="comment" autoResize rows="3" class="w-full" placeholder="Escribe un comentario..."
            :readonly="!isFirstApproved || isFullyApproved" :title="!isFirstApproved
              ? 'Solo editable tras aprobar la Primera Validación'
              : (isFullyApproved ? 'Solo lectura: validación completada' : '')" />

          <small class="text-gray-500">
            Se guarda al confirmar la Segunda Validación
            <template v-if="selectedAccount?.comment"> • Último guardado: {{ selectedAccount.comment }}</template>
            <template v-if="isFullyApproved"> • Solo lectura: validación completada</template>
          </small>
        </div>
      </div>

      <!-- Auditoría (compacta) -->
      <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
        <!-- Primera -->
        <div class="p-2 rounded-md border border-amber-300 bg-amber-50">
          <p class="text-[10px] text-amber-700 font-semibold uppercase tracking-wide">Primera Validación</p>
          <p class="mt-1 text-[11px] leading-tight text-gray-700">
            <span class="text-gray-500">Actualizado por:</span>
            <span class="font-medium">
              {{ selectedAccount.updated0_by_name ?? selectedAccount.updated0_by ?? '—' }}
            </span>
            <span class="mx-1 text-gray-400">•</span>
            <span class="text-gray-500">Fecha:</span>
            <span class="font-medium">
              {{ formatDateTime(selectedAccount.updated0_at) }}
            </span>
          </p>
        </div>

        <!-- Segunda -->
        <div class="p-2 rounded-md border border-indigo-300 bg-indigo-50">
          <p class="text-[10px] text-indigo-700 font-semibold uppercase tracking-wide">Segunda Validación</p>
          <p class="mt-1 text-[11px] leading-tight text-gray-700">
            <span class="text-gray-500">Actualizado por:</span>
            <span class="font-medium">
              {{ selectedAccount.updated_by_name ?? selectedAccount.updated_by ?? '—' }}
            </span>
            <span class="mx-1 text-gray-400">•</span>
            <span class="text-gray-500">Fecha:</span>
            <span class="font-medium">
              {{ formatDateTime(selectedAccount.updated_last_at ?? selectedAccount.updates_last_at) }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <!-- PRIMERA VALIDACIÓN -->
      <div v-if="!isFirstApproved"
        class="w-full flex flex-col gap-3 border-2 border-amber-300 bg-amber-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide flex items-center gap-2">
          <i class="pi pi-file"></i> Primera Validación
        </p>

        <div v-if="!hasAnyAttachment" class="text-xs text-amber-800 bg-amber-100 border border-amber-300 rounded p-2">
          Debes adjuntar y subir al menos un archivo para poder <strong>aprobar</strong> esta etapa.
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Button label="Cerrar" icon="pi pi-times" @click="showDialog = false" />
          <Button label="Aprobar" icon="pi pi-check" severity="success" :loading="loading && actionBusy === 'approve'"
            :disabled="!hasAnyAttachment" :title="!hasAnyAttachment ? 'Sube al menos un archivo antes de aprobar' : ''"
            @click="approveWithFiles()" />

          <!-- OBSERVAR abre popup con input -->
          <Button label="Observar" icon="pi pi-eye" severity="info" :loading="loading && actionBusy === 'observe'"
            @click="openObserveFirstDialog" />

          <Button label="Rechazar" icon="pi pi-times" severity="danger" :loading="loading && actionBusy === 'reject'"
            @click="changeStatus0('rejected', { closeAfter: true })" />
        </div>
      </div>

      <!-- SEGUNDA VALIDACIÓN -->
      <div v-else class="w-full flex flex-col gap-3 border-2 border-indigo-300 bg-indigo-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide flex items-center gap-2">
          <i class="pi pi-check-circle"></i> Segunda Validación
        </p>

        <div class="flex flex-wrap items-center gap-2">
          <Button label="Cerrar" icon="pi pi-times" @click="showDialog = false" />
          <Button label="Aprobar" icon="pi pi-check" severity="success" :loading="loading && actionBusy === 'approve2'"
            :disabled="isFullyApproved" @click="confirmSecond('approved', 'aprobar')" />

          <!-- OBSERVAR abre popup con input -->
          <Button label="Observar" icon="pi pi-eye" severity="info" :loading="loading && actionBusy === 'observe2'"
            :disabled="isFullyApproved" @click="openObserveSecondDialog" />

          <Button label="Rechazar" icon="pi pi-times" severity="danger" :loading="loading && actionBusy === 'reject2'"
            :disabled="isFullyApproved" @click="confirmSecond('rejected', 'rechazar')" />
        </div>
      </div>
    </template>
  </Dialog>

  <ConfirmDialog />

  <!-- Popup: Observación 1ª Validación -->
  <Dialog v-model:visible="showObserveFirst" header="Observar — Primera Validación" :modal="true"
    :style="{ width: '620px' }">
    <div class="space-y-2">
      <p class="text-sm text-gray-600">Mensaje para el cliente:</p>
      <Textarea v-model="observeMessage" :maxlength="OBSERVE_MAX" rows="7" autoResize class="w-full"
        placeholder="Escribe el mensaje que se enviará por correo (máx. 500 caracteres)" />
      <div class="mt-1 text-xs flex items-center justify-between">
        <small class="text-gray-500">Este mensaje se enviará por correo al cliente.</small>
        <span :class="observeCount >= OBSERVE_MAX - 20 ? 'text-red-600' : 'text-gray-500'">
          {{ observeCount }}/{{ OBSERVE_MAX }}
        </span>
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="cancelObserveFirst" />
      <Button label="Observar" icon="pi pi-eye" severity="info" @click="confirmObserveFirst" />
    </template>
  </Dialog>

  <!-- Popup: Observación 2ª Validación -->
  <Dialog v-model:visible="showObserveSecond" header="Observar — Segunda Validación" :modal="true"
    :style="{ width: '620px' }">
    <div class="space-y-2">
      <p class="text-sm text-gray-600">Mensaje para el cliente:</p>
      <Textarea v-model="observeMessage" :maxlength="OBSERVE_MAX" rows="7" autoResize class="w-full"
        placeholder="Escribe el mensaje que se enviará por correo (máx. 500 caracteres)" />
      <div class="mt-1 text-xs flex items-center justify-between">
        <small class="text-gray-500">Este mensaje se enviará por correo al cliente.</small>
        <span :class="observeCount >= OBSERVE_MAX - 20 ? 'text-red-600' : 'text-gray-500'">
          {{ observeCount }}/{{ OBSERVE_MAX }}
        </span>
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="cancelObserveSecond" />
      <Button label="Observar" icon="pi pi-eye" severity="info" @click="confirmObserveSecond" />
    </template>
  </Dialog>

  <!-- Modal de imagen grande -->
  <Dialog v-model:visible="showImageModal" header="Vista de Imagen" :modal="true"
    :style="{ width: '90vw', maxWidth: '1100px' }">
    <div class="flex items-center justify-center">
      <img v-if="previewImageUrl" :src="previewImageUrl" alt="Vista previa"
        class="max-h-[75vh] w-auto object-contain rounded-lg" />
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea';
import { debounce } from 'lodash';

const sortField = ref<string | null>(null); // e.g. 'banco', 'inversionista', etc.
const sortOrder = ref<number | null>(null); // 1 (asc) | -1 (desc)

const onSort = (event: any) => {
  sortField.value = event.sortField;   // e.g. 'banco'
  sortOrder.value = event.sortOrder;   // 1 | -1
  // re-fetch current page (keeps pagination in sync),
  // then the local sort block in loadAccounts() will re-order this page.
  loadAccounts({ first: first.value, rows: rowsPerPage.value });

};



type Status0Api = 'approved' | 'observed' | 'rejected';
type StatusApi = 'approved' | 'observed' | 'rejected';
type BusyKey = 'approve' | 'observe' | 'reject' | 'approve2' | 'observe2' | 'reject2' | null;

type UploadedFile = {
  id?: string;
  original_name?: string;
  name?: string;
  url?: string;
  download_url?: string;
  mime_type?: string;
  size?: number;
};

const toast = useToast();
const confirm = useConfirm();

const OBSERVE_MAX = 500;

const accounts = ref<any[]>([]);
const selectedAccounts = ref<any[]>([]);
const loading = ref(false);
const actionBusy = ref<BusyKey>(null);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const selectedAccount = ref<any>(null);
const showDialog = ref(false);

// Comentarios
const comment0 = ref<string>(''); // primera
const comment = ref<string>(''); // segunda

// Archivos
const uploadedFiles = ref<UploadedFile[]>([]);

// Mostrar si hay algún adjunto
const hasAnyAttachment = computed(() => uploadedFiles.value.length > 0);

const imageFiles = computed(() =>
  uploadedFiles.value.filter((f) => {
    const n = (f.original_name ?? f.name ?? '').toLowerCase();
    const m = (f.mime_type ?? '').toLowerCase();
    const byMime = m.startsWith('image/');
    const byExt = /\.(png|jpe?g|webp|gif|heic)$/i.test(n);
    return !!f.url && (byMime || byExt);
  })
);

// ===== Modal de imagen =====
const showImageModal = ref(false);
const previewImageUrl = ref<string | null>(null);
const openImageModal = (url?: string) => {
  if (!url) return;
  previewImageUrl.value = url;
  showImageModal.value = true;
};

const first = ref(0); // index of the first row in the current page



// ¿Primera validación aprobada?
const isFirstApproved = computed(() => {
  const es = selectedAccount.value?.estado0;
  const api = selectedAccount.value?.status0;
  return es === 'Aprobado' || api === 'approved';
});

// ¿Ambas aprobadas?
const isFullyApproved = computed(() => {
  const s0 = selectedAccount.value?.status0
    ?? (selectedAccount.value?.estado0 === 'Aprobado' ? 'approved' : null);
  const s = selectedAccount.value?.status
    ?? (selectedAccount.value?.estado === 'Aprobado' ? 'approved' : null);
  return s0 === 'approved' && s === 'approved';
});

const loadAccounts = async (event: any = {}) => {
  loading.value = true;
  if (event.first != null) first.value = event.first;
  if (event.rows != null) rowsPerPage.value = Number(event.rows);

  const perPage = rowsPerPage.value;
  const page = Math.floor(first.value / perPage) + 1;


  try {
    const { data: payload } = await axios.get('/ban', {
      params: { search: globalFilter.value, page, perPage }
    });

    accounts.value = payload.data ?? [];


    // Client-side sort of CURRENT PAGE ONLY (lazy table)
    if (sortField.value && sortOrder.value) {
      const dir = sortOrder.value === 1 ? 1 : -1;
      const field = String(sortField.value);

      accounts.value = [...accounts.value].sort((a, b) => {
        const va = a?.[field];
        const vb = b?.[field];

        // number-first compare
        const na = typeof va === 'string' ? Number(va) : va;
        const nb = typeof vb === 'string' ? Number(vb) : vb;
        const bothNums = Number.isFinite(na) && Number.isFinite(nb);
        if (bothNums) return (na - nb) * dir;

        // fallback: string compare (locale-aware, numeric)
        const sa = (va ?? '').toString();
        const sb = (vb ?? '').toString();
        return sa.localeCompare(sb, undefined, { numeric: true, sensitivity: 'base' }) * dir;
      });
    }



    const meta = payload.meta ?? {};
    totalRecords.value = meta.total ?? 0;
    // Keep `first` as-is so we don't jump pages.
    // Keep `rowsPerPage` unless you want to adopt server’s per_page:
    rowsPerPage.value = meta.per_page ? Number(meta.per_page) : rowsPerPage.value;

  } catch (error) {
    console.error('Error al cargar cuentas bancarias:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar las cuentas bancarias', life: 5000 });
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => { first.value = 0; loadAccounts(); }, 500);

const onPage = (event: any) => { loadAccounts(event); };

const getStatusSeverity = (estado: string) => {
  switch (estado) {
    case 'Aprobado': return 'success';
    case 'Observado': return 'info';
    case 'Rechazado': return 'danger';
    case 'Pendiente': return 'warn';
    default: return 'secondary';
  }
};
const getStatus0Severity = (estado0: string) => {
  switch (estado0) {
    case 'Aprobado': return 'success';
    case 'Observado': return 'info';
    case 'Rechazado': return 'danger';
    case 'Pendiente': return 'warn';
    default: return 'secondary';
  }
};

const getConclusionSeverity = (estado: string) => {
  switch (estado) {
    case 'Aprobado': return 'success';
    case 'Rechazado': return 'danger';
    case 'Pendiente': return 'warn';
    case 'Registro Iconcluso': return 'secondary';
    default: return 'secondary';
  }
};

const openShowModal = async (row: any) => {
  selectedAccount.value = row;
  comment0.value = row.comment0 ?? '';
  comment.value = row.comment ?? '';
  uploadedFiles.value = [];
  showDialog.value = true;
  await loadAttachments();
};

/** ---------- CONFIRMS (genéricos) ---------- **/
const confirmFirst = (newStatus0: Status0Api, actionText: 'aprobar' | 'observar' | 'rechazar') => {
  if (!selectedAccount.value) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No se ha seleccionado ninguna cuenta', life: 3000 });
    return;
  }
  confirm.require({
    message: `¿Está seguro que desea ${actionText} la cuenta bancaria de ${selectedAccount.value.inversionista}?`,
    header: `Confirmar ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
    icon: newStatus0 === 'approved'
      ? 'pi pi-check-circle'
      : (newStatus0 === 'rejected' ? 'pi pi-exclamation-triangle' : 'pi pi-pencil'),
    rejectClass: 'p-button-secondary p-button-outlined',
    rejectLabel: 'Cancelar',
    acceptLabel: actionText.charAt(0).toUpperCase() + actionText.slice(1),
    accept: () => changeStatus0(newStatus0, { closeAfter: false }),
    reject: () => toast.add({ severity: 'info', summary: 'Cancelado', detail: 'Operación cancelada', life: 3000 })
  });
};

const confirmSecond = (newStatus: StatusApi, actionText: 'aprobar' | 'observar' | 'rechazar') => {
  if (!selectedAccount.value) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No se ha seleccionado ninguna cuenta', life: 3000 });
    return;
  }
  confirm.require({
    message: `¿Está seguro que desea ${actionText} la cuenta bancaria de ${selectedAccount.value.inversionista}?`,
    header: `Confirmar ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
    icon: newStatus === 'approved' ? 'pi pi-check-circle' : (newStatus === 'rejected' ? 'pi pi-exclamation-triangle' : 'pi pi-pencil'),
    rejectClass: 'p-button-secondary p-button-outlined',
    rejectLabel: 'Cancelar',
    acceptLabel: actionText.charAt(0).toUpperCase() + actionText.slice(1),
    accept: () => changeStatusSecond(newStatus),
    reject: () => toast.add({ severity: 'info', summary: 'Cancelado', detail: 'Operación cancelada', life: 3000 })
  });
};
/** -------------------------------- **/

// Segunda Validación (cambia status) — admite notify_message para "observed"
const changeStatusSecond = async (
  newStatus: 'approved' | 'observed' | 'rejected',
  opts?: { notifyMessage?: string }
) => {
  if (!selectedAccount.value?.id) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay cuenta seleccionada', life: 3000 });
    return;
  }

  actionBusy.value = newStatus === 'approved' ? 'approve2' : newStatus === 'observed' ? 'observe2' : 'reject2';

  loading.value = true;
  try {
    await axios.patch(`/ban/${selectedAccount.value.id}/status`, {
      status: newStatus,
      comment: comment.value,
      notify_message: newStatus === 'observed' ? (opts?.notifyMessage ?? null) : null
    });

    const mapEs = { approved: 'Aprobado', observed: 'Observado', rejected: 'Rechazado' } as const;

    selectedAccount.value.status = newStatus;
    selectedAccount.value.estado = mapEs[newStatus];
    selectedAccount.value.comment = comment.value;

    if (newStatus !== 'approved') {
      selectedAccount.value.status0 = 'pending';
      selectedAccount.value.estado0 = 'Pendiente';
    }

    toast.add({ severity: 'success', summary: 'Actualizado', detail: `Estado cambiado a "${mapEs[newStatus]}"`, life: 3500 });

    showDialog.value = false;
    selectedAccount.value = null;
    await loadAccounts();
  } catch (error: any) {
    const msg = error.response?.data?.message || error.message || 'No se pudo actualizar el estado';
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
  } finally {
    loading.value = false;
    actionBusy.value = null;
  }
};

// Adjuntos
const loadAttachments = async () => {
  if (!selectedAccount.value?.id) return;
  try {
    const { data } = await axios.get(`/ban/${selectedAccount.value.id}/attachments`);
    uploadedFiles.value = (data?.files ?? data?.data ?? []) as UploadedFile[];
  } catch (e: any) {
    const msg = e.response?.data?.message || e.message || 'No se pudieron cargar los adjuntos';
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
  }
};

// Subida automática al seleccionar archivos
const onAutoUpload = async (e: any) => {
  await uploadFiles(e.files ?? []);
};

const uploadFiles = async (files: File[]) => {
  if (!selectedAccount.value?.id) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay cuenta seleccionada', life: 3000 });
    return false;
  }
  if (!files || files.length === 0) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Selecciona al menos un archivo', life: 3000 });
    return false;
  }

  loading.value = true;
  try {
    const form = new FormData();
    files.forEach((f) => form.append('files[]', f));
    const { data } = await axios.post(`/ban/${selectedAccount.value.id}/attachments`, form, { headers: { 'Content-Type': 'multipart/form-data' } });
    const returned = (data?.files ?? []) as UploadedFile[];
    if (returned.length) {
      uploadedFiles.value.push(...returned);
    } else {
      uploadedFiles.value.push(...files.map((f) => ({ name: f.name } as UploadedFile)));
    }
    toast.add({ severity: 'success', summary: 'Listo', detail: 'Archivo(s) subido(s) correctamente', life: 3000 });
    return true;
  } catch (error: any) {
    const msg = error.response?.data?.message || error.message || 'No se pudieron subir los archivos';
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
    return false;
  } finally {
    loading.value = false;
  }
};

const downloadAttachment = (file: UploadedFile) => {
  const url = file.download_url ?? file.url;
  if (!url) {
    toast.add({ severity: 'warn', summary: 'Aviso', detail: 'No hay URL de descarga para este archivo', life: 3000 });
    return;
  }
  window.open(url, '_blank', 'noopener');
};

// Primera Validación (status0)
const approveWithFiles = async () => {
  actionBusy.value = 'approve';
  if (!hasAnyAttachment.value) {
    toast.add({ severity: 'warn', summary: 'Falta adjunto', detail: 'Adjunta al menos un archivo antes de aprobar.', life: 4000 });
    actionBusy.value = null;
    return;
  }
  await changeStatus0('approved', { closeAfter: false });
  actionBusy.value = null;
};

// Primera Validación — admite notify_message para "observed"
const changeStatus0 = async (
  newStatus0: Status0Api,
  opts: { closeAfter?: boolean; notifyMessage?: string } = { closeAfter: true }
) => {
  if (!selectedAccount.value?.id) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay cuenta seleccionada', life: 3000 });
    return;
  }
  actionBusy.value = newStatus0 === 'approved' ? 'approve' : newStatus0 === 'observed' ? 'observe' : 'reject';

  loading.value = true;
  try {
    await axios.patch(`/ban/${selectedAccount.value.id}/status0`, {
      status0: newStatus0,
      comment0: comment0.value,
      notify_message: newStatus0 === 'observed' ? (opts?.notifyMessage ?? null) : null
    });

    const msgMap: Record<Status0Api, string> = { approved: 'Aprobado', observed: 'Observado', rejected: 'Rechazado' };

    selectedAccount.value.status0 = newStatus0;
    selectedAccount.value.estado0 = msgMap[newStatus0];
    selectedAccount.value.comment0 = comment0.value;

    // Al tocar status0, status => pending
    selectedAccount.value.status = 'pending';
    selectedAccount.value.estado = 'Pendiente';

    toast.add({ severity: 'success', summary: 'Actualizado', detail: `Estado 0 cambiado a "${msgMap[newStatus0]}"`, life: 3500 });

    if (opts.closeAfter === false) {
      await loadAttachments();
    } else {
      showDialog.value = false;
      selectedAccount.value = null;
    }
    await loadAccounts();
  } catch (error: any) {
    const msg = error.response?.data?.message || error.message || 'No se pudo cambiar el Estado 0';
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
  } finally {
    loading.value = false;
    actionBusy.value = null;
  }
};

const prettySize = (bytes: number) => {
  if (bytes === 0 || isNaN(bytes)) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`;
};

const formatDateTime = (val: any) => {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d.getTime())) return String(val);
  return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }).format(d);
};

/** ---------- OBSERVAR POPUPS ---------- **/
const showObserveFirst = ref(false);
const showObserveSecond = ref(false);
const observeMessage = ref<string>('');
const observeCount = computed(() => observeMessage.value.length);

// abrir popups
const openObserveFirstDialog = () => {
  observeMessage.value = '';
  showObserveFirst.value = true;
};
const openObserveSecondDialog = () => {
  observeMessage.value = '';
  showObserveSecond.value = true;
};

// cancelar
const cancelObserveFirst = () => { showObserveFirst.value = false; };
const cancelObserveSecond = () => { showObserveSecond.value = false; };

// confirmar (recorta/limpia a 500)
const confirmObserveFirst = async () => {
  const message = observeMessage.value.slice(0, OBSERVE_MAX).trim();
  comment0.value = message; // opcional: registrar
  showObserveFirst.value = false;
  await changeStatus0('observed', { closeAfter: false, notifyMessage: message });
};

const confirmObserveSecond = async () => {
  const message = observeMessage.value.slice(0, OBSERVE_MAX).trim();
  showObserveSecond.value = false;
  await changeStatusSecond('observed', { notifyMessage: message });
};
/** ----------------------------------- **/

onMounted(() => { loadAccounts(); });
</script>

<template>
  <DataTable ref="dt" v-model:selection="selectedAccounts" :value="accounts" dataKey="id" :paginator="true"
    :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" :loading="loading"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} cuentas" @page="onPage" scrollable
    scrollHeight="574px" class="p-datatable-sm">
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
    <Column field="estado0" header="1º estado" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.estado0" :severity="getStatus0Severity(data.estado0)" />
      </template>
    </Column>

    <Column field="updated0_by_name" header="1º apr. user" sortable style="min-width: 8rem" />

    <Column header="1º apr. fecha" style="min-width: 15rem" sortable>
      <template #body="{ data }">{{ data.updated0_at ?? '—' }}</template>
    </Column>


    <!-- Estado principal -->
    <Column field="estado" header="2º estado" sortable style="min-width: 10rem">
      <template #body="{ data }">
        <Tag :value="data.estado" :severity="getStatusSeverity(data.estado)" />
      </template>
    </Column>

    <Column field="updated_by_name" header="2º apr. user" sortable style="min-width: 8rem" />
    <Column header="2º apr. fecha" sortable style="min-width: 15rem">
      <template #body="{ data }">{{ data.updated_last_at ?? '—' }}</template>
    </Column>


    <Column field="creacion" header="Creación" sortable style="min-width: 15rem" />

    <!-- Auditoría -->


    <!-- Acciones -->
    <Column header="" style="min-width: 7rem; text-align:center">
      <template #body="{ data }">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-eye" text rounded @click="openShowModal(data)" />
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

      <!-- Auditoría detallada -->
      <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-3 rounded border border-amber-300 bg-amber-50">
          <p class="text-xs text-amber-700 font-semibold uppercase tracking-wide">Auditoría Primera Validación</p>
          <p class="text-xs text-gray-500 mt-2">Actualizado por</p>
          <p class="font-medium">{{ selectedAccount.updated0_by_name ?? selectedAccount.updated0_by ?? '—' }}</p>
          <p class="text-xs text-gray-500 mt-2">Fecha</p>
          <p class="font-medium">{{ formatDateTime(selectedAccount.updated0_at) }}</p>
        </div>

        <div class="p-3 rounded border border-indigo-300 bg-indigo-50">
          <p class="text-xs text-indigo-700 font-semibold uppercase tracking-wide">Auditoría Segunda Validación</p>
          <p class="text-xs text-gray-500 mt-2">Actualizado por</p>
          <p class="font-medium">{{ selectedAccount.updated_by_name ?? selectedAccount.updated_by ?? '—' }}</p>
          <p class="text-xs text-gray-500 mt-2">Fecha</p>
          <p class="font-medium">{{ formatDateTime(selectedAccount.updated_last_at ?? selectedAccount.updates_last_at)
          }}
          </p>
        </div>
      </div>

      <!-- ADJUNTOS (ambas etapas; uploader solo en 1ra) -->
      <div class="sm:col-span-2 mt-2">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Adjuntos</p>

        <div v-if="!isFirstApproved">
          <FileUpload name="files[]" :customUpload="true" multiple :auto="false" accept=".pdf,image/*"
            :maxFileSize="10485760" chooseLabel="Seleccionar" uploadLabel="Subir" cancelLabel="Limpiar"
            @select="onFileSelect" @uploader="onFileUpload" @remove="onFileRemove" @clear="onFileClear" />

          <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="p-3 rounded border border-gray-200/60">
              <p class="text-xs font-semibold text-gray-500 mb-1">Pendientes de subir</p>
              <ul class="text-sm list-disc pl-5">
                <li v-for="(f, i) in pendingFiles" :key="i">{{ f.name }} ({{ prettySize(f.size) }})</li>
                <li v-if="pendingFiles.length === 0" class="text-gray-500">—</li>
              </ul>
            </div>

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

        <!-- Previsualizaciones SIEMPRE -->
        <div v-if="imageFiles.length" class="mt-4">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Previsualizaciones</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div v-for="(img, idx) in imageFiles" :key="img.id ?? img.url ?? idx"
              class="border rounded-lg overflow-hidden" title="Click para abrir en nueva pestaña">
              <a :href="img.url" target="_blank" rel="noopener">
                <img :src="img.url" :alt="img.original_name ?? 'imagen'" class="w-full h-36 object-cover"
                  loading="lazy" />
              </a>
              <div class="p-2 text-xs truncate">{{ img.original_name ?? img.name }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- COMENTARIOS (ambas etapas; el editable cambia según etapa) -->
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
    </div>

    <!-- Footer -->
    <template #footer>
      <!-- PRIMERA VALIDACIÓN -->
      <div v-if="!isFirstApproved"
        class="w-full flex flex-col gap-3 border-2 border-amber-300 bg-amber-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide flex items-center gap-2">
          <i class="pi pi-file"></i> Primera Validación
        </p>

        <!-- BOTH COMMENTS here (comment0 editable, comment readonly) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <p class="text-xs font-semibold text-gray-600 mb-1">Comentario (Primera)</p>
            <Textarea v-model="comment0" autoResize rows="3" class="w-full" placeholder="Escribe un comentario..." />
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-600 mb-1">Comentario (Segunda)</p>
            <Textarea v-model="comment" autoResize rows="3" class="w-full" placeholder="Solo lectura en esta etapa"
              readonly />
          </div>
        </div>

        <div v-if="!hasAnyAttachment" class="text-xs text-amber-800 bg-amber-100 border border-amber-300 rounded p-2">
          Debes adjuntar y subir al menos un archivo para poder <strong>aprobar</strong> esta etapa.
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Button label="Cerrar" icon="pi pi-times" @click="showDialog = false" />
          <Button label="Aprobar" icon="pi pi-check" severity="success" :loading="loading && actionBusy === 'approve'"
            :disabled="!hasAnyAttachment" :title="!hasAnyAttachment ? 'Sube al menos un archivo antes de aprobar' : ''"
            @click="approveWithFiles()" />
          <Button label="Observar" icon="pi pi-eye" severity="info" :loading="loading && actionBusy === 'observe'"
            @click="changeStatus0('observed', { closeAfter: false })" />
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
          <Button label="Observar" icon="pi pi-eye" severity="info" :loading="loading && actionBusy === 'observe2'"
            :disabled="isFullyApproved" @click="confirmSecond('observed', 'observar')" />
          <Button label="Rechazar" icon="pi pi-times" severity="danger" :loading="loading && actionBusy === 'reject2'"
            :disabled="isFullyApproved" @click="confirmSecond('rejected', 'rechazar')" />
        </div>
      </div>
    </template>
  </Dialog>

  <ConfirmDialog />
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

const accounts = ref<any[]>([]);
const selectedAccounts = ref<any[]>([]);
const loading = ref(false);
const actionBusy = ref<BusyKey>(null);
const totalRecords = ref(0);
const globalFilter = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);
const selectedAccount = ref<any>(null);
const showDialog = ref(false);

// Comentarios
const comment0 = ref<string>(''); // primera
const comment = ref<string>(''); // segunda

// Archivos
const pendingFiles = ref<File[]>([]);
const uploadedFiles = ref<UploadedFile[]>([]);

// Mostrar si hay algún adjunto
const hasAnyAttachment = computed(() => pendingFiles.value.length > 0 || uploadedFiles.value.length > 0);

const imageFiles = computed(() =>
  uploadedFiles.value.filter((f) => {
    const n = (f.original_name ?? f.name ?? '').toLowerCase();
    const m = (f.mime_type ?? '').toLowerCase();
    const byMime = m.startsWith('image/');
    const byExt = /\.(png|jpe?g|webp|gif|heic)$/i.test(n);
    return !!f.url && (byMime || byExt);
  })
);

// ¿Primera validación aprobada?
const isFirstApproved = computed(() => {
  const es = selectedAccount.value?.estado0;
  const api = selectedAccount.value?.status0;
  return es === 'Aprobado' || api === 'approved';
});

// <script setup> section
const isFullyApproved = computed(() => {
  const s0 = selectedAccount.value?.status0
    ?? (selectedAccount.value?.estado0 === 'Aprobado' ? 'approved' : null);
  const s = selectedAccount.value?.status
    ?? (selectedAccount.value?.estado === 'Aprobado' ? 'approved' : null);
  return s0 === 'approved' && s === 'approved';
});

const loadAccounts = async (event: any = {}) => {
  loading.value = true;
  const page = event.page != null ? event.page + 1 : currentPage.value;
  const perPage = event.rows != null ? Number(event.rows) : rowsPerPage.value;

  try {
    const response = await axios.get('/ban', { params: { search: globalFilter.value, page, perPage } });
    const payload = response.data;
    accounts.value = payload.data ?? [];
    totalRecords.value = payload.meta?.total ?? payload.total ?? 0;
    currentPage.value = page;
    rowsPerPage.value = perPage;
  } catch (error) {
    console.error('Error al cargar cuentas bancarias:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar las cuentas bancarias', life: 5000 });
  } finally {
    loading.value = false;
  }
};

const onGlobalSearch = debounce(() => { currentPage.value = 1; loadAccounts(); }, 500);
const onPage = (event: any) => { loadAccounts(event); };

const getStatusSeverity = (estado: string) => {
  switch (estado) {
    case 'Aprobado': return 'success';
    case 'Rechazado': return 'danger';
    case 'Observado': return 'info';
    case 'Pendiente': return 'warn';
    case 'Preaprobado': return 'warn';
    case 'Válido': return 'success';
    case 'Inválido': return 'danger';
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

const openShowModal = async (row: any) => {
  selectedAccount.value = row;
  comment0.value = row.comment0 ?? '';
  comment.value = row.comment ?? '';
  pendingFiles.value = [];
  uploadedFiles.value = [];
  showDialog.value = true;
  await loadAttachments();
};

// Segunda Validación (cambia status)
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

const changeStatusSecond = async (newStatus: 'approved' | 'observed' | 'rejected') => {
  if (!selectedAccount.value?.id) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay cuenta seleccionada', life: 3000 });
    return;
  }

  actionBusy.value = newStatus === 'approved' ? 'approve2' : newStatus === 'observed' ? 'observe2' : 'reject2';

  loading.value = true;
  try {
    await axios.patch(`/ban/${selectedAccount.value.id}/status`, { status: newStatus, comment: comment.value });

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
const onFileSelect = (e: any) => { pendingFiles.value.push(...(e.files ?? [])); };
const onFileRemove = (e: any) => { const f: File = e.file; pendingFiles.value = pendingFiles.value.filter((pf) => pf !== f); };
const onFileClear = () => { pendingFiles.value = []; };
const onFileUpload = async (e: any) => { await uploadFiles(e.files ?? pendingFiles.value); };

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
    const uploadedNames = new Set(files.map((f) => f.name));
    pendingFiles.value = pendingFiles.value.filter((pf) => !uploadedNames.has(pf.name));
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
    toast.add({ severity: 'warn', summary: 'Falta adjunto', detail: 'Adjunta y sube al menos un archivo antes de aprobar.', life: 4000 });
    actionBusy.value = null;
    return;
  }
  if (pendingFiles.value.length > 0) {
    const ok = await uploadFiles(pendingFiles.value.slice());
    if (!ok) { actionBusy.value = null; return; }
  }
  await changeStatus0('approved', { closeAfter: false });
  actionBusy.value = null;
};

const changeStatus0 = async (newStatus0: Status0Api, opts: { closeAfter?: boolean } = { closeAfter: true }) => {
  if (!selectedAccount.value?.id) {
    toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'No hay cuenta seleccionada', life: 3000 });
    return;
  }
  actionBusy.value = newStatus0 === 'approved' ? 'approve' : newStatus0 === 'observed' ? 'observe' : 'reject';

  loading.value = true;
  try {
    await axios.patch(`/ban/${selectedAccount.value.id}/status0`, { status0: newStatus0, comment0: comment0.value });

    const msgMap: Record<Status0Api, string> = { approved: 'Aprobado', observed: 'Observado', rejected: 'Rechazado' };

    selectedAccount.value.status0 = newStatus0;
    selectedAccount.value.estado0 = msgMap[newStatus0];
    selectedAccount.value.comment0 = comment0.value;

    // Cuando se toca status0, status => pending
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

onMounted(() => { loadAccounts(); });
</script>

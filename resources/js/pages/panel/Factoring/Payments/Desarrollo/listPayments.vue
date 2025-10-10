<template>
    <DataTable ref="dt" :value="paginatedInvoices" v-model:selection="selectedInvoices" dataKey="id" :paginator="true"
        :rows="rowsPerPage" :totalRecords="filteredInvoices.length" :first="(currentPage - 1) * rowsPerPage"
        :loading="loading" @page="onPage" :rowsPerPageOptions="[5, 10, 20]" scrollable scrollHeight="574px"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} facturas" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">
                    Facturas
                    <Tag severity="contrast" :value="contadorInvoices" />
                </h4>
                <div class="flex flex-wrap gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="globalFilterValue" @input="onGlobalSearch" placeholder="Buscar..." />
                    </IconField>
                    <Button icon="pi pi-refresh" outlined rounded aria-label="Refresh" @click="loadInvoices" />
                </div>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="codigo" header="Código" sortable style="min-width: 8rem" />

        <Column style="min-width: 8rem" field="ruc_cliente" header="Ruc Cliente" sortable>
            <template #body="slotProps">
                <span v-tooltip.top="slotProps.data.ruc_cliente"
                    style="display:inline-block; max-width:15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ slotProps.data.ruc_cliente }}
                </span>
            </template>
        </Column>
        <Column style="min-width: 8rem" field="razonSocial" header="Razón Social" sortable>
            <template #body="slotProps">
                <span v-tooltip.top="slotProps.data.razonSocial"
                    style="display:inline-block; max-width:15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ slotProps.data.razonSocial }}
                </span>
            </template>
        </Column>

        <Column field="ruc_proveedor" header="Ruc Proveedor" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span v-tooltip.top="slotProps.data.ruc_proveedor"
                    style="display:inline-block; max-width:15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ slotProps.data.ruc_proveedor }}
                </span>
            </template>
        </Column>

        <Column field="montoFactura" header="Monto Factura" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoFactura }}
            </template>
        </Column>


        <Column field="montoAsumidoZuma" header="Monto Asumido Zuma" sortable style="min-width: 13rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoAsumidoZuma }}
            </template>
        </Column>
        <Column field="montoDisponible" header="Monto Disponible" sortable style="min-width: 11rem">
            <template #body="slotProps">
                {{ slotProps.data.moneda }} {{ slotProps.data.montoDisponible }}
            </template>
        </Column>
        <Column field="tasa" header="Tasa" sortable style="min-width: 5rem">
            <template #body="slotProps">
                {{ slotProps.data.tasa }}%
            </template>
        </Column>
        <Column field="moneda" header="Moneda" sortable style="min-width: 5rem" />
        <Column field="fechaPago" header="Fecha Pago" sortable style="min-width: 8rem" />
        <Column field="approval1_status" header="1ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <Tag v-if="slotProps.data.approval1_status" :value="getApprovalLabel(slotProps.data.approval1_status)"
                    :severity="getApprovalSeverity(slotProps.data.approval1_status)" />
                <span v-else>-</span>
            </template>
        </Column>


        <Column field="porcentajeMetaTerceros" header="% Obj Terceros" sortable style="min-width: 10rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.porcentajeMetaTerceros ? 'italic' : ''">
            {{ slotProps.data.porcentajeMetaTerceros || '-' }}
          </span>
        </template>
      </Column>

      <Column field="porcentajeInversionTerceros" header="% Invertido Terceros" sortable style="min-width: 12rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.porcentajeInversionTerceros ? 'italic' : ''">
            {{ slotProps.data.porcentajeInversionTerceros || '-' }}
          </span>
        </template>
      </Column>


      <Column field="fechaPago" header="Fecha de Pago" sortable style="min-width: 18rem">
        <template #body="slotProps">
          <span :class="!slotProps.data.fechaPago ? 'italic' : ''">
            {{ slotProps.data.fechaPago || '-' }}
          </span>
        </template>
      </Column>

      <Column field="fechaCreacion" header="Fecha Creación" sortable style="min-width: 13rem" />


        <Column field="approval1_by" header="1ª Usuario" sortable style="min-width: 16rem">
            <template #body="slotProps">
                {{ slotProps.data.approval1_by || '-' }}
            </template>
        </Column>

        <Column field="approval1_at" header="T. 1ª Aprobación" sortable style="min-width: 11rem">
            <template #body="slotProps">
                {{ slotProps.data.approval1_at || '-' }}
            </template>
        </Column>

        <Column field="approval2_status" header="2ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <Tag v-if="slotProps.data.approval2_status" :value="getApprovalLabel(slotProps.data.approval2_status)"
                    :severity="getApprovalSeverity(slotProps.data.approval2_status)" />
                <span v-else>-</span>
            </template>
        </Column>

        <Column field="approval2_by" header="2do Usuario" sortable style="min-width: 15rem">
            <template #body="slotProps">
                {{ slotProps.data.approval2_by || '-' }}
            </template>
        </Column>

        <Column field="approval2_at" header="T. 2ª Aprobación" sortable style="min-width: 11rem">
            <template #body="slotProps">
                {{ slotProps.data.approval2_at || '-' }}
            </template>
        </Column>

        <Column field="estado" header="Estado Conclusion" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <Tag :value="getStatusLabel(slotProps.data.estado)"
                    :severity="getStatusSeverity(slotProps.data.estado)" />
            </template>
        </Column>

        <Column field="fechaCreacion" header="Fecha Creación" sortable style="min-width: 15rem" />

        <Column header="Acciones" :exportable="false" style="min-width: 8rem">
            <template #body="slotProps">
                <Button icon="pi pi-eye" outlined rounded severity="info" @click="abrirDialog(slotProps.data.id)"
                    aria-label="Ver detalles" />
            </template>
        </Column>
    </DataTable>

    <!-- Dialog -->
    <Dialog v-model:visible="dialogVisible" modal header="Detalles de Factura" :style="{ width: '90vw' }"
        :maximizable="true">
        <ShowPayment v-if="selectedInvoiceId" :invoice-id="selectedInvoiceId" @pago-confirmado="onPagoConfirmado" />
        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" text @click="dialogVisible = false" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import { debounce } from 'lodash';
import ShowPayment from './ShowPayment.vue';

const invoices = ref<any[]>([]);
const loading = ref(false);
const contadorInvoices = ref(0);
const selectedInvoices = ref<any[]>([]);

const globalFilterValue = ref('');
const rowsPerPage = ref(10);
const currentPage = ref(1);

const dialogVisible = ref(false);
const selectedInvoiceId = ref<string | null>(null);

const loadInvoices = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/invoices/filtrado');
        // Mapear los datos de pagos al nivel de la factura
        invoices.value = response.data.data.map((invoice: any) => {
            const primerPago = invoice.pagos && invoice.pagos.length > 0 ? invoice.pagos[0] : null;
            return {
                ...invoice,
                approval1_status: primerPago?.approval1_status || null,
                approval1_by: primerPago?.approval1_by || null,
                approval1_at: primerPago?.approval1_at || null,
                approval2_status: primerPago?.approval2_status || null,
                approval2_by: primerPago?.approval2_by || null,
                approval2_at: primerPago?.approval2_at || null
            };
        });
        contadorInvoices.value = response.data.total;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error al cargar facturas:', error);
    } finally {
        loading.value = false;
    }
};

const abrirDialog = (invoiceId: string) => {
    selectedInvoiceId.value = invoiceId;
    dialogVisible.value = true;
};

const onPagoConfirmado = () => {
    dialogVisible.value = false;
    loadInvoices();
};

const filteredInvoices = computed(() => {
    const search = globalFilterValue.value.toLowerCase();
    return invoices.value.filter((inv: any) =>
        inv.codigo.toLowerCase().includes(search) ||
        inv.razonSocial.toLowerCase().includes(search) ||
        inv.moneda.toLowerCase().includes(search) ||
        inv.estado.toLowerCase().includes(search)
    );
});

const paginatedInvoices = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredInvoices.value.slice(start, start + rowsPerPage.value);
});

const onGlobalSearch = debounce(() => {
    currentPage.value = 1;
}, 500);

const onPage = (event: any) => {
    currentPage.value = event.page + 1;
    rowsPerPage.value = event.rows;
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'inactive': return 'Inactivo';
        case 'active': return 'Activo';
        case 'expired': return 'Expirado';
        case 'judicialized': return 'Judicializado';
        case 'reprogramed': return 'Reprogramado';
        case 'paid': return 'Pagado';
        case 'canceled': return 'Cancelado';
        case 'daStandby': return 'En Espera';
        default: return status;
    }
};

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'active': return 'success';
        case 'paid': return 'success';
        case 'inactive': return 'secondary';
        case 'daStandby': return 'warn';
        case 'reprogramed': return 'warn';
        case 'expired': return 'danger';
        case 'judicialized': return 'danger';
        case 'canceled': return 'danger';
        default: return 'info';
    }
};

const getApprovalLabel = (status: string) => {
    switch (status) {
        case 'pending': return 'Pendiente';
        case 'approved': return 'Aprobado';
        case 'rejected': return 'Rechazado';
        default: return status;
    }
};

const getApprovalSeverity = (status: string) => {
    switch (status) {
        case 'approved': return 'success';
        case 'pending': return 'warn';
        case 'rejected': return 'danger';
        default: return 'info';
    }
};

onMounted(() => {
    loadInvoices();
});
</script>
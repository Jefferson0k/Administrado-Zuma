<template>
    <DataTable ref="dt" :value="withdraws" dataKey="id" :paginator="true" :rows="10" :filters="filters"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} retiros" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Gestionar Retiros</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 1rem" :exportable="false" />
        <Column field="invesrionista" header="Inversionista" sortable style="min-width: 30rem"></Column>
        <Column field="documento" header="Documento" sortable style="min-width: 7rem"></Column>
        <Column field="currency" header="Moneda" sortable style="min-width: 7rem"></Column>
        <Column field="amount" header="Monto" sortable style="min-width: 10rem">
            <template #body="slotProps">
                {{ formatCurrency(slotProps.data.amount, slotProps.data.currency) }}
            </template>
        </Column>
        <Column field="tipo_banco" header="T. Cuenta" sortable style="min-width: 7rem"></Column>
        <Column field="cc" header="N. de cuenta CC" sortable style="min-width: 10rem"></Column>
        <Column field="cci" header="N. de cuenta interbancario CCI" sortable style="min-width: 17rem"></Column>
        <Column field="created_at" header="Fecha de Creación" sortable style="min-width: 12rem"></Column>
        <Column field="approval1_status" header="1ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <template v-if="slotProps.data.approval1_status">
                    <Tag :value="getStatusLabel(slotProps.data.approval1_status)"
                        :severity="getStatusSeverity(slotProps.data.approval1_status)" />
                </template>
                <template v-else>
                    <span class="italic text-gray-500">Sin dato</span>
                </template>
            </template>
        </Column>
        <Column field="approval1_by" header="1ª Usuario" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <span :class="slotProps.data.approval1_by === 'Sin aprobar' ? 'italic' : ''">
                    {{ slotProps.data.approval1_by || 'Sin asignar' }}
                </span>
            </template>
        </Column>
        <Column field="approval1_at" header="T. 1ª Aprobación" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span :class="!slotProps.data.approval1_at ? 'italic' : ''">
                    {{ slotProps.data.approval1_at || 'Sin tiempo' }}
                </span>
            </template>
        </Column>
        <Column field="approval2_status" header="2ª Aprobador" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <template v-if="slotProps.data.approval2_status">
                    <Tag :value="getStatusLabel(slotProps.data.approval2_status)"
                        :severity="getStatusSeverity(slotProps.data.approval2_status)" />
                </template>
                <template v-else>
                    <span class="italic text-gray-500">Sin dato</span>
                </template>
            </template>
        </Column>
        <Column field="approval2_by" header="2do Usuario" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <span :class="slotProps.data.approval2_by === 'Sin aprobar' ? 'italic' : ''">
                    {{ slotProps.data.approval2_by || 'Sin asignar' }}
                </span>
            </template>
        </Column>
        <Column field="approval2_at" header="T. 2ª Aprobación" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span :class="!slotProps.data.approval2_at ? 'italic' : ''">
                    {{ slotProps.data.approval2_at || 'Sin tiempo' }}
                </span>
            </template>
        </Column>
        <Column field="status" header="Estado Conclusión" sortable style="min-width: 11rem">
            <template #body="slotProps">
                <template v-if="!slotProps.data.status">
                    <span class="italic">Sin estado</span>
                </template>
                <template v-else>
                    <Tag :value="getStatusLabel(slotProps.data.status)"
                        :severity="getStatusSeverity(slotProps.data.status)" />
                </template>
            </template>
        </Column>
        <Column header="Acciones" style="min-width: 22rem">
            <template #body="slotProps">
                <div class="flex gap-2 flex-wrap">
                    <!-- 1ª Validación -->
                    <Button v-if="!slotProps.data.approval1_status || slotProps.data.approval2_status === 'observed'"
                        label="1ª Valid." icon="pi pi-check-circle" size="small" severity="warning"
                        @click="openFirstApprovalDialog(slotProps.data)" />

                    <!-- 2ª Validación -->
                    <Button
                        v-else-if="slotProps.data.approval1_status === 'approved' && isEmptyOrPending(slotProps.data.approval2_status)"
                        label="2ª Valid." icon="pi pi-shield" size="small" severity="success"
                        @click="openSecondApprovalDialog(slotProps.data)" />

                    <!-- Detalle -->
                    <Button v-else label="Validaciones" icon="pi pi-list-check" size="small" severity="help" outlined
                        @click="openValidationDetails(slotProps.data)" />


                    <!-- Mostrar Pagar SOLO si NO hay comprobante ni comentario -->
                    <Button v-if="!slotProps.data.resource_path && !slotProps.data.payment_comment" label="Pagar"
                        icon="pi pi-credit-card" size="small" severity="secondary" outlined
                        @click="openPayDialog(slotProps.data)" />

                    <!-- Si ya existe pago, mostrar detalle -->
                    <Button v-else label="Pago" icon="pi pi-paperclip" size="small" severity="secondary" outlined
                        @click="openPaymentDetails(slotProps.data)" />




                    <!-- Detalles del retiro / inversionista que ya tienes -->
                    <Button label="Detalles" icon="pi pi-eye" size="small" severity="info" outlined
                        @click="openDetailsDialog(slotProps.data)" />

                    <Button label="Historial" icon="pi pi-history" size="small" severity="contrast" outlined
                        @click="openHistory(slotProps.data)" />
                </div>

                <!-- Dentro del template #body del Column de acciones -->

            </template>
        </Column>

    </DataTable>



    <!-- Dialog para primera validación -->
    <Dialog v-model:visible="firstApprovalDialog" :style="{ width: '600px' }" :header="selectedWithdraw
        ? `Primera Validación — ${formatCurrency(selectedWithdraw.amount, selectedWithdraw.currency)}`
        : 'Primera Validación'" :modal="true">

        <div class="flex flex-col gap-6">
            <!-- Información del retiro -->
            <div class="flex items-center gap-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                <i class="pi pi-check-circle text-3xl text-orange-500" />
                <div class="flex-1">
                    <p class="font-semibold text-lg">{{ selectedWithdraw?.invesrionista }}</p>
                    <p class="text-sm text-gray-600">
                        Documento: {{ selectedWithdraw?.documento }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Monto: {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Cuenta: {{ selectedWithdraw?.cc }} | CCI: {{ selectedWithdraw?.cci }}
                    </p>
                </div>
            </div>


            <!-- Formulario de validación -->
            <form @submit.prevent="confirmFirstApproval" class="grid grid-cols-1 gap-4">
                <div class="flex flex-col gap-1">
                    <label for="nro_operation" class="font-medium">
                        Número de Operación <span class="text-red-500">*</span>
                    </label>
                    <InputText id="nro_operation" v-model="firstApprovalForm.nro_operation"
                        placeholder="Ej: OP123456789" maxlength="50"
                        :class="{ 'p-invalid': firstApprovalSubmitted && !firstApprovalForm.nro_operation }" />
                    <small v-if="firstApprovalSubmitted && !firstApprovalForm.nro_operation" class="p-error">
                        El número de operación es requerido
                    </small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="deposit_pay_date" class="font-medium">
                        Fecha de Pago <span class="text-red-500">*</span>
                    </label>
                    <DatePicker id="deposit_pay_date" v-model="firstApprovalForm.deposit_pay_date" dateFormat="dd/mm/yy"
                        showIcon placeholder="Seleccionar fecha"
                        :class="{ 'p-invalid': firstApprovalSubmitted && !firstApprovalForm.deposit_pay_date }" />
                    <small v-if="firstApprovalSubmitted && !firstApprovalForm.deposit_pay_date" class="p-error">
                        La fecha de pago es requerida
                    </small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="description" class="font-medium">Descripción</label>
                    <Textarea id="description" v-model="firstApprovalForm.description" rows="3"
                        placeholder="Descripción del pago..." />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="approval1_comment" class="font-medium">Comentario de Validación (Opcional)</label>
                    <Textarea id="approval1_comment" v-model="firstApprovalForm.approval1_comment" rows="2"
                        placeholder="Comentarios sobre la validación..." />
                </div>
            </form>
        </div>

        <template #footer>
            <div class="flex flex-wrap justify-between w-full gap-2">
                <div class="flex gap-2">
                    <Button label="Observar" icon="pi pi-eye-slash" severity="help" outlined
                        :disabled="firstApprovalProcessing" @click="observeStepOne" />
                    <Button label="Rechazar" icon="pi pi-times-circle" severity="danger" outlined
                        :disabled="firstApprovalProcessing" @click="rejectStepOne" />
                </div>

                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" outlined
                        :disabled="firstApprovalProcessing" @click="closeFirstApprovalDialog" />
                    <Button label="Aprobar Primera Validación" icon="pi pi-check" severity="warning"
                        :loading="firstApprovalProcessing" @click="confirmFirstApproval" />
                </div>
            </div>
        </template>

    </Dialog>

    <!-- Dialog para segunda validación -->
    <Dialog v-model:visible="secondApprovalDialog" :style="{ width: '520px' }" :header="selectedWithdraw
        ? `Segunda Validación — ${formatCurrency(selectedWithdraw.amount, selectedWithdraw.currency)}`
        : 'Segunda Validación'" :modal="true">

        <div v-if="secondApprovalLoading" class="flex items-center gap-3 p-4">
            <i class="pi pi-spin pi-spinner text-xl"></i>
            <span>Cargando datos de la 1ª validación…</span>
        </div>

        <div v-else class="flex flex-col gap-4">
            <!-- Bloque de resumen -->
            <div class="p-4 rounded border border-green-200 bg-green-50">
                <div class="flex items-start gap-3">
                    <i class="pi pi-shield text-2xl text-green-500"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-base">{{ selectedWithdraw?.invesrionista }}</p>
                        <p class="text-sm text-gray-700">
                            Monto: {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            ID Retiro: {{ selectedWithdraw?.id }} · Creado: {{ selectedWithdraw?.created_at }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Datos provenientes de la 1ª validación -->
            <div class="p-4 rounded border border-orange-200 bg-orange-50">
                <p class="text-xs font-semibold uppercase text-orange-700 tracking-wide">Datos de la 1ª validación</p>

                <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">N° Operación:</span>
                        <span class="font-medium">{{ selectedWithdraw?.nro_operation || '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">Fecha de pago:</span>
                        <span class="font-medium">
                            {{ selectedWithdraw?.deposit_pay_date ? formatDateTime(selectedWithdraw?.deposit_pay_date) :
                                '—'
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">Descripción:</span>
                        <span class="font-medium text-right whitespace-pre-line">
                            {{ selectedWithdraw?.description || '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">Comentario 1ª valid.:</span>
                        <span class="font-medium text-right whitespace-pre-line">
                            {{ selectedWithdraw?.approval1_comment || '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">1º Usuario:</span>
                        <span class="font-medium">
                            {{ selectedWithdraw?.approval1_by || '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-gray-600">T. 1ª Aprobación:</span>
                        <span class="font-medium">
                            {{ selectedWithdraw?.approval1_at ? selectedWithdraw.approval1_at : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Comentario de segunda validación -->
            <div class="flex flex-col gap-1">
                <label for="approval2_comment" class="font-medium">Comentario de Segunda Validación</label>
                <Textarea id="approval2_comment" v-model="secondApprovalForm.approval2_comment" rows="3"
                    placeholder="Comentarios finales de validación..." />
            </div>

            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm">
                    <strong>⚠️ Importante:</strong> Esta acción aprobará definitivamente el retiro y marcará el
                    movimiento
                    como válido. Se enviará notificación al inversionista.
                </p>
            </div>
        </div>

        <template #footer>
            <!-- tu footer actual sin cambios -->
            <div class="flex flex-wrap justify-between w-full gap-2">
                <div class="flex gap-2">
                    <Button label="Observar" icon="pi pi-eye-slash" severity="help" outlined
                        :disabled="secondApprovalProcessing || secondApprovalLoading" @click="observeStepTwo" />
                    <Button label="Rechazar" icon="pi pi-times-circle" severity="danger" outlined
                        :disabled="secondApprovalProcessing || secondApprovalLoading" @click="rejectStepTwo" />
                </div>

                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" text severity="secondary"
                        :disabled="secondApprovalProcessing || secondApprovalLoading"
                        @click="closeSecondApprovalDialog" />
                    <Button label="Aprobar Definitivamente" icon="pi pi-check" severity="success"
                        :loading="secondApprovalProcessing" :disabled="secondApprovalLoading"
                        @click="confirmSecondApproval" />
                </div>
            </div>
        </template>
    </Dialog>

    <!-- Dialog: Detalle de Validaciones -->
    <Dialog v-model:visible="validationDetailsDialog" :style="{ width: '560px' }" header="Detalle de Validaciones"
        :modal="true">
        <div class="space-y-4">
            <div class="p-4 rounded border bg-slate-50">
                <p class="font-semibold text-sm text-slate-700">Resumen del Retiro</p>

                <div v-if="selectedWithdraw && !canPay(selectedWithdraw)"
                    class="p-3 rounded border-l-4 bg-amber-50 border-amber-400 text-sm text-amber-800">
                    Este retiro aún no está listo para pago. Requiere 2ª validación aprobada y estado final "Aprobado".
                </div>

                <div class="mt-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Inversionista:</span>
                        <span class="font-medium">{{ selectedValidationWithdraw?.invesrionista || '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Monto:</span>
                        <span class="font-medium">
                            {{ formatCurrency(selectedValidationWithdraw?.amount, selectedValidationWithdraw?.currency)
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Creado:</span>
                        <span class="font-medium">{{ selectedValidationWithdraw?.created_at || '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 1ª Validación -->
                <div class="p-4 rounded border border-orange-200 bg-orange-50">
                    <p class="font-semibold text-orange-700 mb-2">1ª Validación</p>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between"><span>Estado:</span><span class="font-medium">{{
                            getStatusLabel(selectedValidationWithdraw?.approval1_status) || '—' }}</span></div>
                        <div class="flex justify-between"><span>Usuario:</span><span class="font-medium">{{
                            selectedValidationWithdraw?.approval1_by || '—' }}</span></div>
                        <div class="flex justify-between"><span>Fecha/Hora:</span><span class="font-medium">{{
                            selectedValidationWithdraw?.approval1_at || '—' }}</span></div>
                        <div><span class="block">Comentario:</span>
                            <p class="font-medium whitespace-pre-line">{{ selectedValidationWithdraw?.approval1_comment
                                ||
                                '—' }}</p>
                        </div>
                        <hr class="my-2" />
                        <div class="flex justify-between"><span>N° Operación:</span><span class="font-medium">{{
                            selectedValidationWithdraw?.nro_operation || '—' }}</span></div>
                        <div class="flex justify-between"><span>Fecha de pago:</span><span class="font-medium">
                                {{ selectedValidationWithdraw?.deposit_pay_date ?
                                    formatDateTime(selectedValidationWithdraw?.deposit_pay_date) : '—' }}
                            </span></div>
                        <div><span class="block">Descripción:</span>
                            <p class="font-medium whitespace-pre-line">{{ selectedValidationWithdraw?.description || '—'
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2ª Validación -->
                <div class="p-4 rounded border border-green-200 bg-green-50">
                    <p class="font-semibold text-green-700 mb-2">2ª Validación</p>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between"><span>Estado:</span><span class="font-medium">{{
                            getStatusLabel(selectedValidationWithdraw?.approval2_status) || '—' }}</span></div>
                        <div class="flex justify-between"><span>Usuario:</span><span class="font-medium">{{
                            selectedValidationWithdraw?.approval2_by || '—' }}</span></div>
                        <div class="flex justify-between"><span>Fecha/Hora:</span><span class="font-medium">{{
                            selectedValidationWithdraw?.approval2_at || '—' }}</span></div>
                        <div><span class="block">Comentario:</span>
                            <p class="font-medium whitespace-pre-line">{{ selectedValidationWithdraw?.approval2_comment
                                ||
                                '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" severity="secondary" text @click="closeValidationDetails" />
        </template>
    </Dialog>

    <!-- Dialog: Pagar / Subir comprobante -->
    <Dialog v-model:visible="payDialog" :style="{ width: '640px' }"
        :header="selectedWithdraw ? `Pagar — ${formatCurrency(selectedWithdraw.amount, selectedWithdraw.currency)}` : 'Pagar'"
        :modal="true">
        <div class="space-y-5">
            <!-- Estado / Checklist -->
            <div class="p-4 rounded-lg border"
                :class="canPay(selectedWithdraw) ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200'">
                <div class="flex items-center gap-2 mb-2">
                    <i
                        :class="canPay(selectedWithdraw) ? 'pi pi-check-circle text-green-600' : 'pi pi-info-circle text-amber-600'"></i>
                    <p class="font-semibold" :class="canPay(selectedWithdraw) ? 'text-green-800' : 'text-amber-800'">
                        {{ canPay(selectedWithdraw)
                            ? 'Listo para registrar el pago'
                            : 'Aún no se puede pagar' }}
                    </p>
                </div>

                <ul class="text-sm leading-6">
                    <li class="flex items-start gap-2">
                        <i
                            :class="selectedWithdraw?.approval2_status === 'approved' ? 'pi pi-check text-green-600' : 'pi pi-minus text-amber-600'"></i>
                        2ª validación aprobada
                    </li>
                    <li class="flex items-start gap-2">
                        <i
                            :class="selectedWithdraw?.status === 'approved' ? 'pi pi-check text-green-600' : 'pi pi-minus text-amber-600'"></i>
                        Estado final del retiro: <b>Aprobado</b>
                    </li>
                    <li class="flex items-start gap-2">
                        <i
                            :class="!selectedWithdraw?.resource_path ? 'pi pi-check text-green-600' : 'pi pi-times text-red-600'"></i>
                        Sin comprobante registrado previamente
                    </li>
                </ul>
            </div>

            <!-- Resumen compacto -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="p-3 rounded border bg-slate-50">
                    <p class="text-slate-600">Inversionista</p>
                    <p class="font-medium truncate" :title="selectedWithdraw?.invesrionista">{{
                        selectedWithdraw?.invesrionista || '—' }}</p>
                </div>
                <div class="p-3 rounded border bg-slate-50">
                    <p class="text-slate-600">Monto</p>
                    <p class="font-medium">
                        {{ formatCurrency(selectedWithdraw?.amount, selectedWithdraw?.currency) }}
                    </p>
                </div>
                <div class="p-3 rounded border bg-slate-50">
                    <p class="text-slate-600">N° Operación (1ª valid.)</p>
                    <p class="font-medium">{{ selectedWithdraw?.nro_operation || '—' }}</p>
                </div>
            </div>

            <!-- Dropzone de archivo -->
            <div>
                <label class="font-medium block mb-2">
                    Comprobante (PDF o imagen) <span class="text-red-500">*</span>
                </label>

                <div class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all" :class="[
                    isDragging ? 'border-primary surface-50' : 'border-slate-300 hover:border-primary',
                    paySubmitted && !payForm.file ? 'border-red-400' : ''
                ]" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave" @drop.prevent="onDrop"
                    @click="fileInput?.click()">
                    <input ref="fileInput" type="file" accept="application/pdf,image/*" class="hidden"
                        @change="onPayFileChange" />

                    <div v-if="!payForm.file" class="space-y-2">
                        <i class="pi pi-upload text-3xl"></i>
                        <p class="text-sm">Arrastra y suelta el archivo aquí, o <span class="underline">haz clic para
                                seleccionar</span>.</p>
                        <p class="text-xs text-slate-500">Tamaño máximo: 4&nbsp;MB · Tipos: PDF, JPG, JPEG, PNG</p>
                        <small v-if="paySubmitted && !payForm.file" class="p-error block mt-1">El comprobante es
                            requerido</small>
                    </div>

                    <div v-else class="flex items-center justify-between gap-4 text-left">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded bg-slate-100 flex items-center justify-center overflow-hidden">
                                <img v-if="isImage(payForm.file)" :src="previewUrl" alt="Vista previa"
                                    class="w-12 h-12 object-cover" />
                                <i v-else class="pi pi-file-pdf text-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium truncate" :title="payForm.file.name">{{ payForm.file.name }}</p>
                                <p class="text-xs text-slate-500">{{ formatBytes(payForm.file.size) }}</p>
                            </div>
                        </div>
                        <Button icon="pi pi-times" rounded severity="secondary" aria-label="Quitar"
                            @click.stop="removeFile" />
                    </div>
                </div>
            </div>

            <!-- Comentario -->
            <div>
                <label class="font-medium" for="pay_comment">Comentario (opcional)</label>
                <Textarea id="pay_comment" v-model="payForm.comment" rows="3" placeholder="Comentario para el pago..."
                    class="w-full mt-2" />
                <div class="flex justify-between mt-1 text-xs text-slate-500">
                    <span>Sugerencia: menciona banco, fecha u observaciones útiles.</span>
                    <span>{{ (payForm.comment || '').length }}/2000</span>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="text-xs text-slate-500">
                    Al enviar, se adjuntará el comprobante al retiro seleccionado.
                </div>
                <div class="flex gap-2">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" outlined :disabled="payProcessing"
                        @click="closePayDialog" />
                    <Button label="Enviar Pago" icon="pi pi-upload" severity="success" :loading="payProcessing"
                        :disabled="!selectedWithdraw || !canPay(selectedWithdraw)" @click="confirmPay" />
                </div>
            </div>
        </template>
    </Dialog>



    <Dialog v-model:visible="paymentDetailsDialog" :style="{ width: '520px' }" header="Detalle de Pago" :modal="true">
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-600">Inversionista:</span>
                <span class="font-medium">{{ selectedPayment?.invesrionista || '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-600">Monto:</span>
                <span class="font-medium">{{ formatCurrency(selectedPayment?.amount, selectedPayment?.currency)
                }}</span>
            </div>

            <div class="mt-2 p-3 rounded bg-slate-50">
                <p class="font-semibold mb-1">Comprobante</p>
                <div v-if="selectedPayment?.resource_path || selectedPayment?.resource_url">
                    <a :href="selectedPayment?.resource_path || ('/' + selectedPayment?.resource_path)" target="_blank"
                        class="text-primary underline">
                        Ver archivo
                    </a>
                </div>
                <div v-else>—</div>

                <p class="font-semibold mt-3 mb-1">Comentario</p>
                <p class="whitespace-pre-line">{{ selectedPayment?.payment_comment || '—' }}</p>
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" severity="secondary" text @click="closePaymentDetails" />
        </template>
    </Dialog>




    <!-- Dialog para Ver Detalles del Inversionista -->



    <Dialog v-model:visible="historyVisible" :style="{ width: '900px', maxWidth: '95vw' }"
        header="Historial de Aprobaciones" :modal="true" :closable="true" @hide="cerrarHistorial">
        <div v-if="historyLoading" class="p-4 text-center">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
            <p class="mt-2">Cargando historial...</p>
        </div>

        <div v-else>
            <div v-if="historyRows.length === 0" class="p-4 text-center text-sm text-gray-500">
                Sin registros de historial.
            </div>

            <DataTable v-else :value="historyRows" dataKey="id" class="p-datatable-sm">
                <Column field="id" header="#" style="width: 5rem" />

                <Column header="1º Aprobación" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <Tag :value="getStatusLabel(data.approval1_status)"
                                :severity="getStatusSeverity(data.approval1_status)" />


                        </div>
                    </template>
                </Column>

                <Column header="1º Usuario" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data?.approval1_by?.name ?? data?.approval1_by ?? '—' }}</div>

                        </div>
                    </template>
                </Column>

                <Column header="1º T Aprobación" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data.approval1_at ? formatDateTime(data.approval1_at) : '—' }}</div>

                        </div>
                    </template>
                </Column>

                <Column header="1º Comentario" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data.approval1_comment || '—' }}</div>

                        </div>
                    </template>
                </Column>

                <Column header="2º Aprobación" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <Tag :value="getStatusLabel(data.approval2_status)"
                                :severity="getStatusSeverity(data.approval2_status)" />

                        </div>
                    </template>
                </Column>

                <Column header="2º Usuario" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data?.approval2_by?.name ?? data?.approval2_by ?? '—' }}</div>

                        </div>
                    </template>
                </Column>

                <Column header="2º T Aprobación" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data.approval2_at ? formatDateTime(data.approval2_at) : '—' }}</div>

                        </div>
                    </template>
                </Column>

                <Column header="2º Comentario" style="min-width: 10rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <div>{{ data.approval2_comment || '—' }}</div>

                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </Dialog>


    <AddWithdraw v-model:visible="detailsDialog" :investor-id="selectedInvestorId"
        :current-withdraw="selectedWithdrawForDetails" @close="closeDetailsDialog" />

</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Tag from 'primevue/tag';
import axios from 'axios';
import AddWithdraw from './AddWithdraw.vue';
// Configuración de Axios


// --- Drag & drop / preview helpers for Pay modal ---
import { onBeforeUnmount } from 'vue';

const fileInput = ref(null);
const isDragging = ref(false);
const previewUrl = ref('');

const onDragOver = () => { isDragging.value = true; };
const onDragLeave = () => { isDragging.value = false; };

const isImage = (file) => !!file && /^image\//i.test(file.type);
const formatBytes = (bytes) => {
    if (!bytes && bytes !== 0) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const val = bytes / Math.pow(1024, i);
    return `${val.toFixed(val >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
};

const cleanupPreview = () => {
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = '';
};

const assignFile = (file) => {
    // client-side cap 4MB just to hint early (backend also validates)
    const MAX = 4 * 1024 * 1024;
    if (file && file.size > MAX) {
        toast.add({ severity: 'warn', summary: 'Archivo demasiado grande', detail: 'Máximo 4 MB.', life: 3000 });
        return;
    }
    payForm.value.file = file || null;
    cleanupPreview();
    if (file && isImage(file)) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) assignFile(file);
};

const onPayFileChange = (e) => {
    const f = e?.target?.files?.[0] ?? null;
    assignFile(f);
};

const removeFile = () => {
    assignFile(null);
    if (fileInput.value) fileInput.value.value = '';
};

onBeforeUnmount(cleanupPreview);


const paymentDetailsDialog = ref(false);
const selectedPayment = ref(null);

const openPaymentDetails = async (row) => {
    try {
        const { data } = await axios.get(`/withdraws/${row.id}`);
        selectedPayment.value = data?.data || row;
    } catch (_) {
        selectedPayment.value = row;
    } finally {
        paymentDetailsDialog.value = true;
    }
};

const closePaymentDetails = () => {
    paymentDetailsDialog.value = false;
    selectedPayment.value = null;
};




const canPay = (row) => {
    if (!row) return false;
    // Requiere 2ª validación aprobada + estado final "approved" + que aún NO tenga comprobante
    const approved = row.approval2_status === 'approved' && row.status === 'approved';
    const alreadyPaid = !!row.resource_path; // 👈 usa resource_path (tu backend lo setea)
    return approved && !alreadyPaid;
};



// --- Pagar / Subir comprobante ---
const payDialog = ref(false);
const payProcessing = ref(false);
const paySubmitted = ref(false);
const payForm = ref({
    file: null,
    comment: ''
});



const openPayDialog = async (withdraw) => {
    selectedWithdraw.value = withdraw;
    payForm.value = { file: null, comment: '' };
    paySubmitted.value = false;
    payProcessing.value = false;

    try {
        const { data } = await axios.get(`/withdraws/${withdraw.id}`);
        if (data?.data) selectedWithdraw.value = data.data;
    } catch (e) {
        // keep the row data if fetch fails
    }

    payDialog.value = true;
};


const closePayDialog = () => {
    payDialog.value = false;
    payForm.value = { file: null, comment: '' };
    paySubmitted.value = false;
    payProcessing.value = false;
    // NO limpiamos selectedWithdraw aquí para permitir volver a abrir rápido
};

const confirmPay = async () => {
    paySubmitted.value = true;
    if (!selectedWithdraw.value) return;

    if (!payForm.value.file) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Adjunta el comprobante (PDF o imagen).', life: 3000 });
        return;
    }

    if (!canPay(selectedWithdraw.value)) {
        toast.add({ severity: 'warn', summary: 'No permitido', detail: 'El retiro debe estar aprobado en 2ª validación y en estado Aprobado.', life: 4000 });
        return;
    }

    payProcessing.value = true;
    try {
        const formData = new FormData();
        formData.append('file', payForm.value.file);           // 👈 nombre que valida el backend
        if (payForm.value.comment?.trim()) {
            formData.append('comment', payForm.value.comment.trim()); // 👈 el backend lo espera como 'comment'
        }

        const { data } = await axios.post(
            `/withdraws/${selectedWithdraw.value.id}/upload-voucher`, // 👈 ruta de tu controlador
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        const idx = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (idx !== -1 && data?.data) {
            withdraws.value[idx] = data.data; // si devuelves Resource
        } else {
            await loadWithdraws();
        }

        toast.add({ severity: 'success', summary: 'Pago registrado', detail: data?.message || 'Archivo subido correctamente', life: 4000 });
        closePayDialog();
    } catch (err) {
        console.error('Error al registrar pago:', err?.response ?? err);
        toast.add({ severity: 'error', summary: 'Error al pagar', detail: extractErrorMessage(err, 'No se pudo registrar el pago'), life: 7000 });
    } finally {
        payProcessing.value = false;
    }
};








// Detalle de validaciones
const validationDetailsDialog = ref(false);
const selectedValidationWithdraw = ref(null);

const openValidationDetails = async (row) => {
    try {
        // traer data fresca por si hay cambios recientes
        const { data } = await axios.get(`/withdraws/${row.id}`);
        selectedValidationWithdraw.value = data?.data || row;
    } catch (_) {
        // si falla, mostrar lo que ya tenemos
        selectedValidationWithdraw.value = row;
    } finally {
        validationDetailsDialog.value = true;
    }
};

const closeValidationDetails = () => {
    validationDetailsDialog.value = false;
    selectedValidationWithdraw.value = null;
};






const extractErrorMessage = (err, fallback = 'Ocurrió un error') => {
    try {
        // 1) Backend sent { message: "...", error: "...", errors: {...} }
        const data = err?.response?.data;
        if (typeof data === 'string') return data;
        if (data?.message) {
            // Collect validation errors if they exist
            const bag = data?.errors && typeof data.errors === 'object'
                ? Object.entries(data.errors)
                    .flatMap(([, msgs]) => (Array.isArray(msgs) ? msgs : [String(msgs)]))
                : [];
            const tail = bag.length ? `  • ${bag.join('\n  • ')}` : (data?.error ? `  • ${data.error}` : '');
            return tail ? `${data.message}\n${tail}` : data.message;
        }
        // 2) Fallback to HTTP text / status text
        const status = err?.response?.status ? ` (HTTP ${err.response.status})` : '';
        if (err?.response?.statusText) return `${err.response.statusText}${status}`;
        // 3) Network or generic
        if (err?.message) return err.message;
    } catch (_) { }
    return fallback;
};



axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

const toast = useToast();
const dt = ref();
const withdraws = ref([]);
const detailsDialog = ref(false);
const selectedInvestorId = ref('');
// Estados de diálogos
const firstApprovalDialog = ref(false);
const secondApprovalDialog = ref(false);

// Estados de procesamiento
const firstApprovalProcessing = ref(false);
const secondApprovalProcessing = ref(false);

// Estados de validación
const firstApprovalSubmitted = ref(false);

const selectedWithdraw = ref(null);

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});




const firstApprovalForm = ref({
    nro_operation: '',
    deposit_pay_date: null,
    description: '',
    approval1_comment: ''
});

const secondApprovalForm = ref({
    approval2_comment: ''
});

const selectedWithdrawForDetails = ref(null);



const secondApprovalLoading = ref(false);

const formatDateTime = (val) => {
    if (!val) return '';
    const d = new Date(val);
    // es-PE with America/Lima style
    return d.toLocaleString('es-PE', { hour12: false });
};


const openDetailsDialog = (withdraw) => {
    selectedInvestorId.value = withdraw.investor_id ||
        withdraw.user_id ||
        withdraw.documento ||
        extractInvestorId(withdraw.invesrionista);

    selectedWithdrawForDetails.value = withdraw;   // 👈 save the current withdraw
    detailsDialog.value = true;
};

const closeDetailsDialog = () => {
    detailsDialog.value = false;
    selectedInvestorId.value = '';
    selectedWithdrawForDetails.value = null;       // 👈 clear it
};
const extractInvestorId = (investorText) => {
    if (!investorText) return '';
    const match = investorText.match(/\(ID:\s*(\d+)\)/);
    return match ? match[1] : investorText;
};
onMounted(() => {
    loadWithdraws();
});

const loadWithdraws = async () => {
    try {
        const response = await axios.get('/withdraws');
        withdraws.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error al cargar retiros:', error?.response ?? error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: extractErrorMessage(error, 'Error al cargar los retiros'),
            life: 7000
        });
    }

};











// Funciones para primera validación
const openFirstApprovalDialog = (withdraw) => {
    selectedWithdraw.value = withdraw;
    resetFirstApprovalForm();
    firstApprovalDialog.value = true;
};

const closeFirstApprovalDialog = () => {
    firstApprovalDialog.value = false;
    resetFirstApprovalForm();
    selectedWithdraw.value = null;
};

const resetFirstApprovalForm = () => {
    firstApprovalForm.value = {
        nro_operation: '',
        deposit_pay_date: null,
        description: '',
        approval1_comment: ''
    };
    firstApprovalSubmitted.value = false;
    firstApprovalProcessing.value = false;
};

const confirmFirstApproval = async () => {
    firstApprovalSubmitted.value = true;

    if (!firstApprovalForm.value.nro_operation || !firstApprovalForm.value.deposit_pay_date) {
        toast.add({
            severity: 'warn',
            summary: 'Validación',
            detail: 'Complete todos los campos requeridos',
            life: 3000
        });
        return;
    }

    firstApprovalProcessing.value = true;

    try {
        const payload = {
            nro_operation: firstApprovalForm.value.nro_operation,
            deposit_pay_date: formatDateForBackend(firstApprovalForm.value.deposit_pay_date),
            description: firstApprovalForm.value.description || null,
            approval1_comment: firstApprovalForm.value.approval1_comment || null
        };

        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/approve-step-one`, payload);

        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = {
                ...response.data.data
            };
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Primera validación completada',
            life: 3000
        });

        // cerrar 1ª y abrir 2ª automáticamente con el registro actualizado
        closeFirstApprovalDialog();
        const updatedRow = withdraws.value[withdrawIndex] || response.data.data;
        if (updatedRow) {
            openSecondApprovalDialog(updatedRow);
        }


    } catch (error) {
        console.error('Error en primera validación:', error?.response ?? error);
        toast.add({
            severity: 'error',
            summary: 'Error en 1ª validación',
            detail: extractErrorMessage(error, 'No se pudo completar la 1ª validación'),
            life: 7000
        });
    }

    finally {
        firstApprovalProcessing.value = false;
    }
};


// Funciones para segunda validación
const openSecondApprovalDialog = async (withdraw) => {
    secondApprovalLoading.value = true;
    selectedWithdraw.value = withdraw; // show something immediately
    resetSecondApprovalForm();
    secondApprovalDialog.value = true;

    try {
        // Pull the freshest data so we have step-one fields
        const { data } = await axios.get(`/withdraws/${withdraw.id}`);
        // Expecting { data: { ...fullWithdraw } }
        if (data?.data) {
            selectedWithdraw.value = data.data;
        }
    } catch (e) {
        console.warn('No se pudo cargar detalle completo:', e?.response ?? e);

    }

    finally {
        secondApprovalLoading.value = false;
    }
};


// 1ª VALIDACIÓN: Observar / Rechazar
const observeStepOne = async () => {
    if (!selectedWithdraw.value) return;
    if (!firstApprovalForm.value.approval1_comment?.trim()) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Agrega un comentario para observar', life: 3000 });
        return;
    }
    firstApprovalProcessing.value = true;
    try {
        const { data } = await axios.post(`/withdraws/${selectedWithdraw.value.id}/observe-step-one`, {
            comment: firstApprovalForm.value.approval1_comment
        });
        const idx = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (idx !== -1) withdraws.value[idx] = data.data;
        toast.add({ severity: 'warn', summary: 'Observado', detail: data.message || 'Observado en 1ª validación', life: 3000 });
        closeFirstApprovalDialog();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo observar', life: 5000 });
    } finally {
        firstApprovalProcessing.value = false;
    }
};

const rejectStepOne = async () => {
    if (!selectedWithdraw.value) return;
    if (!firstApprovalForm.value.approval1_comment?.trim()) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Agrega un comentario para rechazar', life: 3000 });
        return;
    }
    firstApprovalProcessing.value = true;
    try {
        const { data } = await axios.post(`/withdraws/${selectedWithdraw.value.id}/reject-step-one`, {
            comment: firstApprovalForm.value.approval1_comment
        });
        const idx = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (idx !== -1) withdraws.value[idx] = data.data;
        toast.add({ severity: 'error', summary: 'Rechazado', detail: data.message || 'Rechazado en 1ª validación', life: 3000 });
        closeFirstApprovalDialog();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo rechazar', life: 5000 });
    } finally {
        firstApprovalProcessing.value = false;
    }
};

// 2ª VALIDACIÓN: Observar / Rechazar
const observeStepTwo = async () => {
    if (!selectedWithdraw.value) return;
    if (!secondApprovalForm.value.approval2_comment?.trim()) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Agrega un comentario para observar', life: 3000 });
        return;
    }
    secondApprovalProcessing.value = true;
    try {
        const { data } = await axios.post(`/withdraws/${selectedWithdraw.value.id}/observe-step-two`, {
            comment: secondApprovalForm.value.approval2_comment
        });
        const idx = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (idx !== -1) withdraws.value[idx] = data.data;
        toast.add({ severity: 'warn', summary: 'Observado', detail: data.message || 'Observado en 2ª validación', life: 3000 });
        closeSecondApprovalDialog();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo observar', life: 5000 });
    } finally {
        secondApprovalProcessing.value = false;
    }
};

const rejectStepTwo = async () => {
    if (!selectedWithdraw.value) return;
    if (!secondApprovalForm.value.approval2_comment?.trim()) {
        toast.add({ severity: 'warn', summary: 'Validación', detail: 'Agrega un comentario para rechazar', life: 3000 });
        return;
    }
    secondApprovalProcessing.value = true;
    try {
        const { data } = await axios.post(`/withdraws/${selectedWithdraw.value.id}/reject-step-two`, {
            comment: secondApprovalForm.value.approval2_comment
        });
        const idx = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (idx !== -1) withdraws.value[idx] = data.data;
        toast.add({ severity: 'error', summary: 'Rechazado', detail: data.message || 'Rechazado en 2ª validación', life: 3000 });
        closeSecondApprovalDialog();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo rechazar', life: 5000 });
    } finally {
        secondApprovalProcessing.value = false;
    }
};


const closeSecondApprovalDialog = () => {
    secondApprovalDialog.value = false;
    resetSecondApprovalForm();
    selectedWithdraw.value = null;
};

const resetSecondApprovalForm = () => {
    secondApprovalForm.value = {
        approval2_comment: ''
    };
    secondApprovalProcessing.value = false;
};

const confirmSecondApproval = async () => {
    secondApprovalProcessing.value = true;

    try {
        const payload = {
            approval2_comment: secondApprovalForm.value.approval2_comment || null
        };

        const response = await axios.post(`/withdraws/${selectedWithdraw.value.id}/approve-step-two`, payload);

        // Actualizar el retiro en la lista
        const withdrawIndex = withdraws.value.findIndex(w => w.id === selectedWithdraw.value.id);
        if (withdrawIndex !== -1) {
            withdraws.value[withdrawIndex] = response.data.data;
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message || 'Segunda validación completada',
            life: 3000
        });

        closeSecondApprovalDialog();

    } catch (error) {
        console.error('Error en segunda validación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Error en la segunda validación',
            life: 5000
        });
    } finally {
        secondApprovalProcessing.value = false;
    }
};

// Funciones utilitarias
const getStatusLabel = (status) => {
    const statusMap = {
        'approved': 'Aprobado',
        'rejected': 'Rechazado',
        'observed': 'Observado',
        'pending': 'Pendiente',
        'valid': 'Válido'
    };
    return statusMap[status] || status;
};

const getStatusSeverity = (status) => {
    const severityMap = {
        'approved': 'success',
        'rejected': 'danger',
        'observed': 'warn',
        'pending': 'info',
        'valid': 'success'
    };
    return severityMap[status] || 'info';
};

const formatCurrency = (value, currency = 'PEN') => {
    if (value) {
        const numValue = parseFloat(value);
        const currencyMap = {
            'PEN': { locale: 'es-PE', currency: 'PEN' },
            'USD': { locale: 'en-US', currency: 'USD' },
            'EUR': { locale: 'de-DE', currency: 'EUR' }
        };

        const config = currencyMap[currency] || currencyMap['PEN'];
        return numValue.toLocaleString(config.locale, {
            style: 'currency',
            currency: config.currency
        });
    }
    return '';
};

const formatDateForBackend = (date) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};


// --- Historial de aprobaciones (lazy via API) ---
// Mantén estos nombres: son los que usa el <Dialog> del template
const historyVisible = ref(false);
const historyLoading = ref(false);
const historyError = ref('');
const historyRows = ref([]);

const openHistory = async (row) => {
    historyVisible.value = true;
    historyLoading.value = true;
    historyError.value = '';
    selectedWithdraw.value = row; // opcional para mostrar resumen

    try {
        const { data } = await axios.get(`/withdraws/${row.id}/approval-history`);
        historyRows.value = Array.isArray(data?.data) ? data.data : [];
    } catch (e) {
        historyError.value = extractErrorMessage(e, 'No se pudo cargar el historial');
        historyRows.value = [];
    } finally {
        historyLoading.value = false;
    }
};

const cerrarHistorial = () => {
    historyVisible.value = false;
    historyRows.value = [];
    historyError.value = '';
};


const isEmptyOrPending = (v) =>
    v === null || v === undefined || v === '' || v === 'pending';



</script>
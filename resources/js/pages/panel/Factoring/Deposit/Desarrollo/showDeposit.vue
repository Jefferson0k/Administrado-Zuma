<template>
  <!-- Dialog principal -->
  <Dialog v-model:visible="dialogVisible" modal header="Detalle del Depósito" :style="{ width: '63rem' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" @hide="handleClose">
    <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-start mb-8">
        <div>
          <h2 class="text-2xl font-bold mb-2">{{ deposit.investor }}</h2>
          <div class="flex items-center gap-4 text-sm">
            <span class="flex items-center gap-1">
              <i class="pi pi-building"></i>
              {{ deposit.nomBanco }}
            </span>
            <span class="flex items-center gap-1">
              <i class="pi pi-calendar"></i>
              {{ deposit.creacion }}
            </span>
            <span class="flex items-center gap-1">
              <i class="pi pi-hashtag"></i>
              {{ deposit.nro_operation }}
            </span>
          </div>
        </div>
        <div class="text-right">
          <div class="text-3xl font-bold text-green-600 mb-1">
            {{ formatAmount(deposit.amount) }}
          </div>
          <div class="text-sm text-gray-500 uppercase tracking-wide">{{ deposit.currency }}</div>
        </div>
      </div>

      <!-- Alerta de cuenta bancaria -->
      <div v-if="deposit.estado_bank_account !== 'approved'" class="mb-8">
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-400 p-6 rounded-r-lg">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <i class="pi pi-exclamation-triangle text-amber-500 text-2xl"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-amber-900 mb-2">Cuenta Bancaria Pendiente de Validación</h3>
              <p class="text-amber-700 mb-3">
                La cuenta bancaria asociada requiere aprobación antes de proceder con las validaciones del depósito.
              </p>
              <div class="inline-flex items-center gap-2">
                <span class="text-sm text-amber-600">Estado:</span>
                <Tag :value="getBankAccountStatusText(deposit.estado_bank_account)"
                  :severity="getBankAccountSeverity(deposit.estado_bank_account)" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenido principal -->
      <div v-if="deposit.estado_bank_account === 'approved'" class="grid grid-cols-1 xl:grid-cols-5 gap-8">
        <!-- Vista principal -->
        <div class="xl:col-span-3">
          <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <i class="pi pi-image text-blue-600"></i>
                Vista principal
              </h3>
              <Button v-if="heroImageUrl" icon="pi pi-external-link" severity="secondary" text size="small"
                @click="openImagePreview" label="Ver en tamaño completo" />
            </div>

            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
              <div v-if="heroImageUrl" class="flex justify-center">
                <div class="relative group cursor-pointer" @click="openImagePreview">
                  <Image :src="heroImageUrl" alt="Voucher / primera imagen" preview
                    class="rounded-lg shadow-lg max-w-full h-auto object-contain"
                    style="max-height: 500px; min-height: 300px;" />
                  <div
                    class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <i class="pi pi-search-plus text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div v-else
                class="flex flex-col items-center justify-center py-16 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                <i class="pi pi-image text-6xl mb-4"></i>
                <p class="text-lg font-medium">No hay imagen principal</p>
                <p class="text-sm">Sube archivos en la sección de adjuntos (debajo).</p>
              </div>
            </div>
          </div>

          <!-- Panel de adjuntos (multi-archivo) -->
          <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="pi pi-paperclip text-slate-600"></i>
              Archivos adjuntos
            </h4>

            <!-- Uploader múltiple (sube automáticamente al seleccionar) -->
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 mb-4">
              <div class="flex flex-col md:flex-row md:items-center gap-3">
                <input ref="filesInput" type="file" multiple
                  class="block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  @change="onFilesPickedAndUpload" />
                <div class="flex items-center gap-2">
                  <ProgressSpinner v-if="uploading" style="width: 20px; height: 20px" strokeWidth="4" />
                  <span v-if="uploading" class="text-sm text-gray-600">Subiendo...</span>
                  <Button v-else-if="filesToUpload.length" icon="pi pi-times" severity="secondary" outlined
                    label="Limpiar selección" @click="clearPickedFiles" />
                </div>
              </div>
              <div v-if="filesToUpload.length" class="mt-3 text-xs text-gray-700 space-y-1">
                <div class="font-medium">Seleccionados:</div>
                <ul class="list-disc list-inside">
                  <li v-for="f in filesToUpload" :key="f.name + f.size">{{ f.name }} — {{ prettySize(f.size) }}</li>
                </ul>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                Cualquier tipo de archivo. Límite recomendado &lt;= 20MB por archivo (validado en backend).
              </p>
            </div>

            <!-- Lista de adjuntos -->
            <div v-if="attachments.length" class="divide-y">
              <div v-for="att in attachments" :key="att.id" class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                  <i class="pi" :class="att.is_image ? 'pi-image' : 'pi-file'"></i>
                  <div class="min-w-0">
                    <a :href="att.url" target="_blank" class="text-blue-600 hover:underline break-all">{{ att.name ||
                      'archivo' }}</a>
                    <div class="text-xs text-gray-500 truncate">{{ att.mime || '—' }} • {{ prettySize(att.size) }}</div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Button v-if="att.is_image" icon="pi pi-eye" text @click="preview(att.url)"
                    v-tooltip="'Vista rápida'" />
                  <Button icon="pi pi-trash" severity="danger" text @click="deleteAttachment(att.id)" />
                </div>
              </div>
            </div>

            <div v-else class="text-sm text-gray-500">
              No hay archivos adjuntos todavía.
            </div>
          </div>
        </div>

        <!-- Panel lateral con detalles -->
        <div class="xl:col-span-2 space-y-6">
          <!-- Información de la cuenta -->
          <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="pi pi-credit-card text-indigo-600"></i>
              Información de Cuenta
            </h4>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Tipo de cuenta</span>
                <span class="font-medium capitalize">{{ deposit.type }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Número de cuenta</span>
                <span class="font-mono text-sm">{{ deposit.cc }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">CCI</span>
                <span class="font-mono text-xs text-gray-500">{{ deposit.cci }}</span>
              </div>
              <div class="flex justify-between items-center pt-2 border-t">
                <span class="text-sm text-gray-600">Estado cuenta</span>
                <Tag :value="getBankAccountStatusText(deposit.estado_bank_account)"
                  :severity="getBankAccountSeverity(deposit.estado_bank_account)" />
              </div>
            </div>
          </div>

          <!-- Estados del proceso -->
          <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="pi pi-cog text-purple-600"></i>
              Estado del Proceso
            </h4>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full"
                    :class="deposit.status0 === 'approved' ? 'bg-green-500' : deposit.status0 === 'pending' ? 'bg-yellow-500' : deposit.status0 === 'observed' ? 'bg-blue-500' : 'bg-red-500'" />
                  <span class="text-sm font-medium">Primera Validación</span>
                </div>
                <Tag :value="translateEstado(deposit.status0)" :severity="getSeverity(deposit.status0)"
                  :icon="getIcon(deposit.status0)" />
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full"
                    :class="deposit.status === 'approved' ? 'bg-green-500' : deposit.status === 'pending' ? 'bg-yellow-500' : deposit.status === 'observed' ? 'bg-blue-500' : 'bg-red-500'" />
                  <span class="text-sm font-medium">Aprobación Final</span>
                </div>
                <template v-if="deposit.status0 === 'rejected'"><span>—</span></template>
                <template v-else>
                  <Tag :value="translateEstado(deposit.status)" :severity="getSeverity(deposit.status)"
                    :icon="getIcon(deposit.status)" />
                </template>

              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de validaciones -->
      <div v-if="deposit.estado_bank_account === 'approved'" class="mt-8">
        <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
          <i class="pi pi-check-circle text-green-600"></i>
          Panel de Validaciones
        </h3>

        <div v-if="loading" class="flex justify-center items-center py-16">
          <ProgressSpinner size="60" strokeWidth="4" />
          <span class="ml-4 text-gray-600">Cargando información del depósito...</span>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Primera Validación -->
          <div
            class="bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                  <i class="pi pi-shield text-yellow-600"></i>
                  Primera Validación
                </h4>
                <p class="text-sm text-gray-600">Validación inicial del movimiento</p>
              </div>
              <Tag :value="translateEstado(deposit.status0)" :severity="getSeverity(deposit.status0)"
                :icon="getIcon(deposit.status0)" class="text-sm" />
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <div class="text-xs text-blue-800 space-y-1">
                <div>
                  <strong>N° Operación:</strong>
                  <span class="font-mono">{{ deposit.nro_operation }}</span>
                </div>
                <div><strong>Estado:</strong> {{ translateEstado(deposit.status0) }}</div>
              </div>
            </div>

            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Comentarios</label>
              <textarea v-model="comment0" :disabled="!canFirstValidation"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none disabled:bg-gray-100 disabled:text-gray-500"
                rows="3" placeholder="Escriba Comentarios para la primera validación..." />
              <div class="text-xs text-gray-500 mt-1" v-if="!canFirstValidation">
                Los comentarios están bloqueados porque esta etapa no está en <strong>pendiente u observada</strong>.
              </div>
              <div class="text-xs text-gray-500 mt-1" v-else-if="comment0">
                {{ comment0.length }} caracteres
              </div>
              <div v-if="canFirstValidation && !hasUpload"
                class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-2 mt-2">
                Para habilitar <strong>Aprobar</strong>, primero sube al menos un archivo en <em>Archivos adjuntos</em>.
              </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
              <Button @click="updateMovement('approved')" :loading="updatingStatus0"
                :disabled="!canApproveFirst || !hasFirstComment || updatingStatus0"
                :title="!hasFirstComment ? 'Escribe un comentario antes de aprobar' : (!hasUpload ? 'Debes subir al menos un archivo' : '')"
                icon="pi pi-check" label="Aprobar" severity="success" class="p-button-sm" />
              <Button @click="openObserveFirstDialog"
                :disabled="!canFirstValidation || !hasFirstComment || updatingStatus0"
                :title="!hasFirstComment ? 'Escribe un comentario antes de observar' : ''" icon="pi pi-eye"
                label="Observar" severity="info" outlined class="p-button-sm" />
              <Button @click="openRejectMovementDialog"
                :disabled="!canFirstValidation || !hasFirstComment || updatingStatus0"
                :title="!hasFirstComment ? 'Escribe un comentario antes de rechazar' : ''" icon="pi pi-times"
                label="Rechazar" severity="danger" outlined class="p-button-sm" />


            </div>
          </div>

          <!-- Segunda Validación -->
          <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                  <i class="pi pi-verified text-blue-600"></i>
                  Aprobación Final
                </h4>
                <p class="text-sm text-gray-600">Confirmación final del depósito</p>
              </div>
              <template v-if="deposit.status0 === 'rejected'"><span>—</span></template>
              <template v-else>
                <Tag :value="translateEstado(deposit.status)" :severity="getSeverity(deposit.status)"
                  :icon="getIcon(deposit.status)" class="text-sm" />
              </template>
            </div>

            <div v-if="deposit.status0 !== 'approved'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div class="flex items-start gap-3">
                <i class="pi pi-lock text-yellow-600 mt-1"></i>
                <div>
                  <p class="font-medium text-yellow-800 mb-1">Esperando Validación</p>
                  <p class="text-sm text-yellow-700">Complete la primera validación para continuar.</p>
                </div>
              </div>
            </div>

            <div v-else-if="deposit.status0 === 'approved' && deposit.status === 'pending'" class="space-y-4">
              <div class="bg-white border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3 mb-4">
                  <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                  <div>
                    <p class="font-medium text-gray-900 mb-1">Listo para Aprobación</p>
                    <p class="text-sm text-gray-600">El movimiento está validado. Proceda con la aprobación final.</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="deposit.estadoConfig === 'valid'"
              class="bg-green-50 border border-green-200 rounded-lg p-4">
              <div class="flex items-center text-green-700">
                <i class="pi pi-check-circle text-xl mr-3"></i>
                <div>
                  <p class="font-medium">Depósito Aprobado</p>
                  <p class="text-sm">El depósito ha sido procesado exitosamente</p>
                </div>
              </div>
            </div>

            <div v-else-if="deposit.status === 'rejected'" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex items-start gap-3 text-red-700">
                <i class="pi pi-times-circle text-xl mt-1"></i>
                <div>
                  <p class="font-medium">Depósito Rechazado</p>
                  <p class="text-sm">El depósito ha sido rechazado</p>
                  <p v-if="deposit.description" class="text-sm mt-1">
                    <strong>Motivo:</strong> {{ deposit.description }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Comentarios (Aprobación Final) -->
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Comentarios</label>
              <textarea v-model="comment1" :disabled="!canSecondValidation"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none disabled:bg-gray-100 disabled:text-gray-500"
                rows="3" placeholder="Escriba Comentarios para la aprobación final..." />
              <div class="text-xs text-gray-500 mt-1" v-if="!canSecondValidation">
                Los comentarios están bloqueados porque esta etapa no está en <strong>pendiente</strong>.
              </div>
              <div class="text-xs text-gray-500 mt-1" v-else-if="comment1">
                {{ comment1.length }} caracteres
              </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
              <Button @click="updateConfirm('approved')" :loading="updatingStatus1"
                :disabled="!canSecondValidation || !hasSecondComment || updatingStatus1"
                :title="!hasSecondComment ? 'Escribe un comentario antes de aprobar' : ''" icon="pi pi-check"
                label="Aprobar" severity="success" class="p-button-sm" />
              <Button @click="openObserveSecondDialog" :loading="updatingStatus1"
                :disabled="!canSecondValidation || !hasSecondComment || updatingStatus1"
                :title="!hasSecondComment ? 'Escribe un comentario antes de observar' : ''" icon="pi pi-eye"
                label="Observar" severity="info" outlined class="p-button-sm" />
              <Button @click="openRejectConfirmDialog"
                :disabled="!canSecondValidation || !hasSecondComment || updatingStatus1"
                :title="!hasSecondComment ? 'Escribe un comentario antes de rechazar' : ''" icon="pi pi-times"
                label="Rechazar" severity="danger" outlined class="p-button-sm" />

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cerrar" icon="pi pi-times" text severity="secondary" @click="handleClose" />
      </div>
    </template>
  </Dialog>

  <!-- Dialogs secundarios: Rechazo -->
  <Dialog v-model:visible="showRejectMovementDialog" modal header="Rechazar Movimiento" :style="{ width: '500px' }">
    <div class="space-y-4">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <i class="pi pi-exclamation-triangle text-red-600 mt-1"></i>
          <div>
            <p class="font-medium text-red-900">¿Confirmar rechazo del movimiento?</p>
            <p class="text-sm text-red-700 mt-1">Esta acción marcará el movimiento como rechazado.</p>
          </div>
        </div>
      </div>
      <div v-if="comment0.trim()" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h5 class="font-medium text-blue-900 mb-2">Observaciones a incluir:</h5>
        <p class="text-sm text-blue-800 italic">"{{ comment0 }}"</p>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancelar" text icon="pi pi-times" severity="secondary" @click="cancelRejectMovement" />
        <Button label="Rechazar" severity="danger" icon="pi pi-times" @click="updateMovement('rejected')"
          :disabled="!canFirstValidation || updatingStatus0" :loading="updatingStatus0" />
      </div>
    </template>
  </Dialog>

  <Dialog v-model:visible="showRejectConfirmDialog" modal header="Rechazar Aprobación del Depósito"
    :style="{ width: '500px' }">
    <div class="space-y-4">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <i class="pi pi-exclamation-triangle text-red-600 mt-1"></i>
          <div>
            <p class="font-medium text-red-900">¿Confirmar rechazo de la aprobación?</p>
            <p class="text-sm text-red-700 mt-1">Esta acción rechazará la confirmación del depósito.</p>
          </div>
        </div>
      </div>
      <div v-if="comment1.trim()" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h5 class="font-medium text-blue-900 mb-2">Observaciones a incluir:</h5>
        <p class="text-sm text-blue-800 italic">"{{ comment1 }}"</p>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancelar" text icon="pi pi-times" severity="secondary" @click="cancelRejectConfirm" />
        <Button label="Rechazar" severity="danger" icon="pi pi-times" @click="updateConfirm('rejected')"
          :disabled="!canSecondValidation || updatingStatus1" :loading="updatingStatus1" />
      </div>
    </template>
  </Dialog>

  <!-- Dialogs secundarios: Observar (con email) -->
  <Dialog v-model:visible="showObserveFirst" modal header="Observar — Primera Validación" :style="{ width: '560px' }">
    <div class="space-y-4">
      <!-- selector de plantilla -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Plantilla de correo a enviar</label>
        <div class="grid grid-cols-1 gap-2">
          <div v-for="opt in observeEmailOptions" :key="opt.key" class="flex items-start gap-3 p-3 rounded-md border"
            :class="observeKey === opt.key ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <input type="radio" :id="'opt-' + opt.key" class="mt-1" :value="opt.key" v-model="observeKey" />
            <label :for="'opt-' + opt.key" class="cursor-pointer">
              <div class="font-medium text-gray-900">{{ opt.label }}</div>
              <div class="text-xs text-gray-600" v-if="opt.hint">{{ opt.hint }}</div>
            </label>
          </div>
        </div>
        <p v-if="observeTouched && !observeKey" class="text-xs text-red-600 mt-1">
          Selecciona una plantilla para continuar.
        </p>
      </div>



    </div>

    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="cancelObserveFirst" />
      <Button label="Observar" icon="pi pi-eye" severity="info" @click="confirmObserveFirst" />
    </template>
  </Dialog>


  <Dialog v-model:visible="showObserveSecond" modal header="Observar — Aprobación Final" :style="{ width: '560px' }">
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Plantilla de correo a enviar</label>
        <div class="grid grid-cols-1 gap-2">
          <div v-for="opt in observeEmailOptions" :key="opt.key" class="flex items-start gap-3 p-3 rounded-md border"
            :class="observeKey2 === opt.key ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <input type="radio" :id="'opt2-' + opt.key" class="mt-1" :value="opt.key" v-model="observeKey2" />
            <label :for="'opt2-' + opt.key" class="cursor-pointer">
              <div class="font-medium text-gray-900">{{ opt.label }}</div>
              <div class="text-xs text-gray-600" v-if="opt.hint">{{ opt.hint }}</div>
            </label>
          </div>
        </div>
        <p v-if="observeTouched2 && !observeKey2" class="text-xs text-red-600 mt-1">
          Selecciona una plantilla para continuar.
        </p>
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" icon="pi pi-times" text @click="cancelObserveSecond" />
      <Button label="Observar" icon="pi pi-eye" severity="info" @click="confirmObserveSecond" />
    </template>
  </Dialog>


  <!-- Dialog imagen completa -->
  <Dialog v-model:visible="showImageDialog" modal header="Vista completa" :style="{ width: '95vw', height: '90vh' }"
    :maximizable="true">
    <div class="flex justify-center items-center h-full p-4">
      <img :src="currentPreview" alt="Vista completa"
        class="max-w-full max-h-full object-contain rounded-lg shadow-lg" />
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Image from 'primevue/image';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';

interface Attachment {
  id: string;
  name: string | null;
  mime: string | null;
  size: number | null;
  url: string | null;
  is_image?: boolean;
  ext?: string | null;
  created_at?: string | null;
}



// --- under other refs/computed ---
const observeKey = ref<string>('');        // clave de la plantilla elegida
const observeTouched = ref(false);


const observeKey2 = ref<string>('');       // plantilla elegida (2ª validación)
const observeTouched2 = ref(false);


// Lista de plantillas disponibles (las claves deben coincidir con backend)
const observeEmailOptions = [
  { key: 'cuenta_origen_ob', label: 'Error de cuenta de origen:', hint: 'La cuenta bancaria de origen seleccionada no coincide con la de la transferencia' },
  { key: 'cuenta_destino_ob', label: 'Error de cuenta de destino:', hint: 'La transferencia se realizó a una cuenta distinta a la de ZUMA' },

];



interface Props { deposit: any; }
const props = defineProps<Props>();
const emit = defineEmits(['close', 'refresh']);
const toast = useToast();

// Limits
const OBSERVE_MAX = 500;

// Dialog control
const dialogVisible = ref(true);

// UI states
const loading = ref(false);
const showRejectMovementDialog = ref(false);
const showRejectConfirmDialog = ref(false);
const showImageDialog = ref(false);

// Observe popups
const showObserveFirst = ref(false);
const showObserveSecond = ref(false);


const observeMessage = ref('');
const observeCount = computed(() => observeMessage.value.length);
const DEFAULT_OBSERVE_MESSAGE =
  'Hemos revisado su depósito y necesitamos información adicional para continuar. Por favor, responda este correo con los datos solicitados. Gracias.';


// Comments
const comment0 = ref('');
const comment1 = ref('');

// Reglas: exigir comentario en cada validación
const hasFirstComment = computed(() => (comment0.value ?? '').trim().length > 0);
const hasSecondComment = computed(() => (comment1.value ?? '').trim().length > 0);

// Attachments local state
const attachments = ref<Attachment[]>([]);

// Uploader
const filesInput = ref<HTMLInputElement | null>(null);
const filesToUpload = ref<File[]>([]);
const uploading = ref(false);

// Computed: hero image (foto)
const heroImageUrl = computed<string | null>(() => {
  return props.deposit.foto || null;
});

// For preview dialog
const tempPreviewUrl = ref<string | null>(null);
const currentPreview = computed(() => tempPreviewUrl.value || heroImageUrl.value);

// Enable/disable buttons
// Primera validación habilitada si está "pending" o "observed"
const canFirstValidation = computed(() =>
  props.deposit.status0 === 'pending' || props.deposit.status0 === 'observed'
);

// Segunda validación solo cuando la primera está "approved" y la segunda "pending"
const canSecondValidation = computed(() =>
  props.deposit.status0 === 'approved' && props.deposit.status === 'pending'
);

// Require at least one uploaded file to enable Approve in first validation
const hasUpload = computed(() => (attachments.value?.length ?? 0) > 0);
const canApproveFirst = computed(() => canFirstValidation.value && hasUpload.value);

// Init
const handleClose = () => {
  dialogVisible.value = false;
  emit('close');
};

const sortByCreatedAsc = (arr: any[]) =>
  [...arr].sort((a, b) =>
    new Date(a?.created_at || 0).getTime() - new Date(b?.created_at || 0).getTime()
  );

const initializeComponent = async () => {
  loading.value = true;

  // Comments initial
  if (props.deposit.comment0) comment0.value = props.deposit.comment0;
  if (props.deposit.comment) comment1.value = props.deposit.comment;

  // Seed attachments from prop if present, otherwise load
  if (Array.isArray(props.deposit.attachments)) {
    attachments.value = sortByCreatedAsc(props.deposit.attachments);
  } else {
    await fetchAttachments();
  }

  loading.value = false;
};

const fetchAttachments = async () => {
  if (!props.deposit?.id) return;
  try {
    const { data } = await axios.get(`/deposit/${props.deposit.id}/attachments`);
    attachments.value = sortByCreatedAsc(data.attachments || []);
  } catch (e: any) {
    // silent, non-critical
  }
};

// ====== Uploader (multiple; uploads immediately on change) ======
const onFilesPickedAndUpload = async (e: Event) => {
  const t = (e.target as HTMLInputElement);
  filesToUpload.value = t.files ? Array.from(t.files) : [];
  if (!filesToUpload.value.length) return;
  await uploadFiles(); // auto-upload
};

const clearPickedFiles = () => {
  filesToUpload.value = [];
  if (filesInput.value) filesInput.value.value = '';
};

const uploadFiles = async () => {
  if (!props.deposit?.id || filesToUpload.value.length === 0) return;
  uploading.value = true;
  try {
    const fd = new FormData();
    filesToUpload.value.forEach(f => fd.append('files[]', f));
    const { data } = await axios.post(`/deposit/${props.deposit.id}/attachments`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const newOnes: Attachment[] = data.attachments || [];
    attachments.value = sortByCreatedAsc([...attachments.value, ...newOnes]);
    props.deposit.attachments = attachments.value;

    toast.add({ severity: 'success', summary: 'Listo', detail: 'Archivos subidos.' });
    clearPickedFiles();
    emit('refresh');
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo subir' });
  } finally {
    uploading.value = false;
  }
};

const deleteAttachment = async (attachmentId: string) => {
  if (!props.deposit?.id) return;
  try {
    await axios.delete(`/deposit/${props.deposit.id}/attachments/${attachmentId}`);
    attachments.value = attachments.value.filter(a => a.id !== attachmentId);
    props.deposit.attachments = attachments.value;
    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Adjunto eliminado.' });
    emit('refresh');
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'No se pudo eliminar' });
  }
};

const preview = (url?: string | null) => {
  if (!url) return;
  tempPreviewUrl.value = url;
  showImageDialog.value = true;
};

// ===== Helpers
const prettySize = (bytes?: number | null) => {
  if (bytes === undefined || bytes === null) return '—';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

function translateEstado(estado: string) {
  const t: Record<string, string> = {
    approved: 'Aprobado',
    observed: 'Observado',
    pending: 'Pendiente',
    rejected: 'Rechazado',
    valid: 'Válido',
    invalid: 'Inválido',
    confirmed: 'Confirmado'
  };
  return t[estado] || estado;
}
function getSeverity(status: string) {
  const s: Record<string, string> = {
    approved: 'success',
    observed: 'info',
    pending: 'warn',
    rejected: 'danger',
    valid: 'success',
    invalid: 'danger',
    confirmed: 'info'
  };
  return s[status] || 'contrast';
}
function getIcon(status: string) {
  const i: Record<string, string> = {
    approved: 'pi pi-check-circle',
    rejected: 'pi pi-ban',
    pending: 'pi pi-clock',
    observed: 'pi pi-eye',
    valid: 'pi pi-check-circle',
    invalid: 'pi pi-times-circle',
    confirmed: 'pi pi-check-circle'
  };
  return i[status] || 'pi pi-question';
}
function getBankAccountStatusText(status: string) {
  const s: Record<string, string> = {
    approved: 'Aprobado',
    observed: 'Observado',
    pending: 'Pendiente',
    rejected: 'Rechazada'
  };
  return s[status] || status;
}
function getBankAccountSeverity(status: string) {
  const s: Record<string, string> = {
    approved: 'success',
    observed: 'warning',
    pending: 'info',
    rejected: 'danger'
  };
  return s[status] || 'contrast';
}

const updatingStatus0 = ref(false);
const updatingStatus1 = ref(false);

// ===== Observe popups handlers
const openObserveFirstDialog = () => {
  observeKey.value = '';
  observeTouched.value = false;
  showObserveFirst.value = true;
};
const openObserveSecondDialog = () => {
  observeKey2.value = '';
  observeTouched2.value = false;
  showObserveSecond.value = true;
};


const cancelObserveFirst = () => { showObserveFirst.value = false; };
const cancelObserveSecond = () => { showObserveSecond.value = false; };

const confirmObserveFirst = async () => {
  observeTouched.value = true;
  if (!observeKey.value) return; // exige selección

  showObserveFirst.value = false;
  await updateMovement('observed', null, observeKey.value);
};



const confirmObserveSecond = async () => {
  observeTouched2.value = true;
  if (!observeKey2.value) return; // exige selección
  showObserveSecond.value = false;
  await updateConfirm('observed', null, observeKey2.value);
};


// ===== Update status (1st validation)
// add third param notifyKey
const updateMovement = async (
  newStatus: 'approved' | 'observed' | 'rejected' | 'pending',
  notifyMessage?: string | null,
  notifyKey?: string | null
) => {
  // ... validations unchanged ...

  updatingStatus0.value = true;
  try {
    const payload: any = {
      status0: newStatus,
      comment0: comment0.value.trim() || null,
      notify_message: newStatus === 'observed' ? (notifyMessage || null) : null,
      notify_key: newStatus === 'observed' ? (notifyKey || null) : null,   // <<--- NEW
    };
    const { data } = await axios.post(`/deposit/${props.deposit.id}/update-status0`, payload);
    // ... rest unchanged ...

    toast.add({
      severity: newStatus === 'rejected' ? 'warn' : newStatus === 'observed' ? 'info' : 'success',
      summary: 'Actualizado',
      detail: data?.message || 'Primera validación actualizada correctamente',
      life: 3000
    });
    props.deposit.status0 = newStatus;
    props.deposit.status = 'pending';
    props.deposit.comment0 = payload.comment0;
    showRejectMovementDialog.value = false;
    dialogVisible.value = false;
    emit('close');
    emit('refresh');
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Error al actualizar la primera validación';
    toast.add({ severity: 'error', summary: 'Error', detail: msg });
  } finally {
    updatingStatus0.value = false;
  }
};

// ===== Update status (2nd validation)
const updateConfirm = async (
  newStatus: 'approved' | 'observed' | 'rejected' | 'pending',
  notifyMessage?: string | null,
  notifyKey?: string | null
) => {
  if (!props.deposit.id) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se encontró el ID del depósito' });
    return;
  }
  if (!(comment1.value ?? '').trim().length) {
    toast.add({ severity: 'warn', summary: 'Comentario requerido', detail: 'Escribe un comentario antes de continuar.' });
    return;
  }
  updatingStatus1.value = true;
  try {

    const payload: any = {
      status: newStatus,
      comment: comment1.value.trim() || null,
      notify_message: newStatus === 'observed' ? (notifyMessage || null) : null,
      notify_key: newStatus === 'observed' ? (notifyKey || null) : null
    };

    const { data } = await axios.post(`/deposit/${props.deposit.id}/update-status`, payload);
    toast.add({
      severity: newStatus === 'rejected' ? 'warn' : newStatus === 'observed' ? 'info' : 'success',
      summary: 'Actualizado',
      detail: data?.message || 'Segunda validación actualizada correctamente',
      life: 3000
    });
    props.deposit.status = newStatus;
    props.deposit.comment = payload.comment;
    showRejectConfirmDialog.value = false;
    dialogVisible.value = false;
    emit('close');
    emit('refresh');
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Error al actualizar la segunda validación';
    toast.add({ severity: 'error', summary: 'Error', detail: msg });
  } finally {
    updatingStatus1.value = false;
  }
};

const openRejectMovementDialog = () => { showRejectMovementDialog.value = true; };
const cancelRejectMovement = () => { showRejectMovementDialog.value = false; };
const openRejectConfirmDialog = () => { showRejectConfirmDialog.value = true; };
const cancelRejectConfirm = () => { showRejectConfirmDialog.value = false; };
const openImagePreview = () => { tempPreviewUrl.value = heroImageUrl.value; showImageDialog.value = true; };

const formatAmount = (amount: number | string) => {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(amount));
};




onMounted(() => { initializeComponent(); });
</script>

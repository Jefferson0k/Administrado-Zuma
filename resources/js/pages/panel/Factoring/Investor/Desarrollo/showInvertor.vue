<template>
  <Dialog v-model:visible="visible" :style="{ width: '88vw', maxWidth: '1100px' }" header="Validación de Inversionista"
    :modal="true" :closable="true" @hide="closeDialog">
    <div>
      <h3 class="text-base font-semibold mb-2 text-gray-900">Información Básica</h3>
      <Message severity="info" :closable="false">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
          <div><span class="text-xs font-medium text-blue-800">Nombre:</span>
            <p class="text-sm">{{ investor.name }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">DNI:</span>
            <p class="text-sm">{{ investor.document }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Teléfono:</span>
            <p class="text-sm">{{ investor.telephone }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Email:</span>
            <p class="text-sm">{{ investor.email }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Validado por IA:</span>
            <p class="text-sm">No</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-blue-800">Estado:</span>
            <Tag :severity="getStatusSeverity(investor.status)" :value="getStatusLabel(investor.status)" />
          </div>
          <div><span class="text-xs font-medium text-blue-800">Persona Expuesta:</span>
            <p class="text-sm">{{ investor.personaexpuesta ? 'Sí' : 'No' }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Relación con PEP:</span>
            <p class="text-sm">{{ investor.relacionPolitica ? 'Sí' : 'No' }}</p>
          </div>
        </div>
      </Message>

      <!-- Documentos DNI -->
      <div class="mb-4 mt-4">
        <h3 class="text-base font-semibold mb-2 text-gray-900 flex items-center gap-2">
          <i class="pi pi-id-card text-blue-600"></i>
          Documentos de Identidad
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
          <!-- DNI Frontal -->
          <div class="border rounded-md overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b">
              <span class="text-xs font-medium text-gray-900">DNI - Parte Frontal</span>
            </div>
            <div class="p-2">
              <div v-if="editableInvestor.document_front">
                <template v-if="isImageLike(toViewUrl(editableInvestor.document_front))">
                  <img :key="toViewUrl(editableInvestor.document_front)"
                    :src="toViewUrl(editableInvestor.document_front)" alt="DNI Frontal"
                    class="w-full h-32 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
                    @error="safeImgOnError" @click="viewDocument(toViewUrl(editableInvestor.document_front))" />
                </template>
                <template v-else>
                  <div class="flex items-center justify-between gap-2 bg-gray-50 border rounded p-2">
                    <div class="text-xs text-gray-700 truncate">
                      Archivo no-imagen (PDF u otro). Ábrelo para visualizarlo.
                    </div>
                    <Button label="Abrir" icon="pi pi-external-link" size="small" outlined
                      @click="viewDocument(toViewUrl(editableInvestor.document_front))" />
                  </div>
                </template>

                <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2"
                  @click="viewDocument(toViewUrl(editableInvestor.document_front))" />
                <Button v-if="!isValidated" label="Observar" icon="pi pi-exclamation-triangle" size="small"
                  severity="warn" class="w-full mt-2" @click="showObserveDialog('dni_front')" />
              </div>

              <div v-else class="text-center py-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
                <i class="pi pi-image text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-600 text-xs">No hay documento frontal</p>
              </div>
            </div>
          </div>

          <!-- DNI Posterior -->
          <div class="border rounded-md overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b">
              <span class="text-xs font-medium text-gray-900">DNI - Parte Posterior</span>
            </div>
            <div class="p-2">
              <div v-if="editableInvestor.document_back">
                <template v-if="isImageLike(toViewUrl(editableInvestor.document_back))">
                  <img :key="toViewUrl(editableInvestor.document_back)" :src="toViewUrl(editableInvestor.document_back)"
                    alt="DNI Posterior"
                    class="w-full h-32 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
                    @error="safeImgOnError" @click="viewDocument(toViewUrl(editableInvestor.document_back))" />
                </template>
                <template v-else>
                  <div class="flex items-center justify-between gap-2 bg-gray-50 border rounded p-2">
                    <div class="text-xs text-gray-700 truncate">
                      Archivo no-imagen (PDF u otro). Ábrelo para visualizarlo.
                    </div>
                    <Button label="Abrir" icon="pi pi-external-link" size="small" outlined
                      @click="viewDocument(toViewUrl(editableInvestor.document_back))" />
                  </div>
                </template>

                <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2"
                  @click="viewDocument(toViewUrl(editableInvestor.document_back))" />
                <Button v-if="!isValidated" label="Observar" icon="pi pi-exclamation-triangle" size="small"
                  severity="warn" class="w-full mt-2" @click="showObserveDialog('dni_back')" />
              </div>

              <div v-else class="text-center py-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
                <i class="pi pi-image text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-600 text-xs">No hay documento posterior</p>
              </div>
            </div>
          </div>

          <!-- Foto del Inversionista -->
          <div class="border rounded-md overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 border-b">
              <span class="text-xs font-medium text-gray-900">Foto del Inversionista</span>
            </div>
            <div class="p-2">
              <div v-if="editableInvestor.investor_photo_path">
                <template v-if="isImageLike(toViewUrl(editableInvestor.investor_photo_path))">
                  <img :key="toViewUrl(editableInvestor.investor_photo_path)"
                    :src="toViewUrl(editableInvestor.investor_photo_path)" alt="Foto del Inversionista"
                    class="w-full h-32 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
                    @error="safeImgOnError" @click="viewDocument(toViewUrl(editableInvestor.investor_photo_path))" />
                </template>
                <template v-else>
                  <div class="flex items-center justify-between gap-2 bg-gray-50 border rounded p-2">
                    <div class="text-xs text-gray-700 truncate">
                      Archivo no-imagen (PDF u otro). Ábrelo para visualizarlo.
                    </div>
                    <Button label="Abrir" icon="pi pi-external-link" size="small" outlined
                      @click="viewDocument(toViewUrl(editableInvestor.investor_photo_path))" />
                  </div>
                </template>

                <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2"
                  @click="viewDocument(toViewUrl(editableInvestor.investor_photo_path))" />
                <Button v-if="!isValidated" label="Observar" icon="pi pi-exclamation-triangle" size="small"
                  severity="warn" class="w-full mt-2" @click="showObserveDialog('investor_photo')" />
              </div>

              <div v-else class="text-center py-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
                <i class="pi pi-user text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-600 text-xs">No hay foto del inversionista</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Alerta si no hay documentos -->
        <div
          v-if="!editableInvestor.document_front && !editableInvestor.document_back && !editableInvestor.investor_photo_path"
          class="text-center py-4 bg-yellow-50 border border-yellow-200 rounded-md mt-3">
          <i class="pi pi-exclamation-triangle text-xl text-yellow-600 mb-1"></i>
          <p class="text-gray-900 text-sm font-medium">No se han subido documentos de identidad</p>
        </div>
      </div>

      <!-- Información adicional -->
      <h3 class="text-base font-semibold mb-2 text-gray-900 mt-4">Información Adicional</h3>
      <Message severity="secondary" :closable="false">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
          <div><span class="text-xs font-medium text-blue-800">Avatar:</span>
            <p class="text-sm">{{ getAvatarDisplay(investor.perfil) }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Departamento:</span>
            <p class="text-sm">{{ departmentName  }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Provincia:</span>
            <p class="text-sm">{{ provinceName  }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Distrito:</span>
            <p class="text-sm">{{ districtName  }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Dirección:</span>
            <p class="text-sm">{{ investor.address }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Fecha de registro:</span>
            <p class="text-sm">{{ investor.creacion }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Email verificado:</span>
            <p class="text-sm">{{ investor.emailverificacion }}</p>
          </div>
          <div><span class="text-xs font-medium text-blue-800">Whatsapp Verificado:</span>
            <p class="text-sm">No</p>
          </div>
        </div>
      </Message>

      <!-- Evidencias -->
      <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Evidencia Espectro -->
        <div>
          <h3 class="text-base font-semibold mb-2 text-gray-900 flex items-center gap-2">
            <i class="pi pi-upload text-blue-600"></i>
            Evidencia Espectro
          </h3>

          <!-- Listado SIEMPRE visible -->
          <div class="space-y-2 mb-3">
            <template v-if="spectroEvidences.length">
              <div v-for="item in spectroEvidences" :key="'spectro-' + item.id"
                class="bg-blue-50 p-3 rounded-md border border-blue-200 flex items-center justify-between">
                <div class="flex items-center gap-2 truncate">
                  <i class="pi pi-file text-blue-600"></i>
                  <div class="truncate">
                    <div class="text-xs font-medium text-blue-800 truncate">{{ item.name }}</div>
                    <div class="text-[11px] text-blue-700/80">{{ item.mime }} • {{ formatSize(item.size) }}</div>
                  </div>
                </div>
                <div class="flex gap-2 shrink-0">
                  <Button label="Ver" icon="pi pi-external-link" size="small" outlined
                    @click="viewDocument(toViewUrl(item.url))" />
                  <Button icon="pi pi-trash" size="small" outlined severity="danger"
                    @click="deleteSpectroEvidence(item.id)" />
                </div>
              </div>
            </template>
            <template v-else>
              <div class="bg-blue-50 p-3 rounded-md border border-blue-200 text-xs text-blue-900">
                Aún no hay evidencias Espectro.
              </div>
            </template>
          </div>

          <!-- Uploader (multi) -->
          <div class="bg-gray-50 p-3 rounded-md border">
            <FileUpload ref="fileUploader" mode="advanced" name="file" accept=".jpg,.jpeg,.png,.pdf"
              :maxFileSize="5120000" :multiple="true" :auto="true" :customUpload="true" @uploader="uploadEvidenceFile"
              @select="onFileSelect" :showUploadButton="false" :showCancelButton="false">
              <template #header="{ chooseCallback, clearCallback, files }">
                <div class="flex justify-between items-center w-full gap-2">
                  <div class="flex gap-2">
                    <Button @click="chooseCallback()" icon="pi pi-plus" rounded outlined size="small" />
                    <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger" size="small"
                      :disabled="!files || files.length === 0" />
                  </div>
                </div>
              </template>

              <template #content="{ files, removeFileCallback }">
                <div v-if="files.length > 0">
                  <div class="flex flex-wrap p-0 gap-3">
                    <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                      class="card m-0 px-4 py-2 flex flex-col border-1 surface-border items-center gap-2 text-center">
                      <img role="presentation" :alt="file.name" :src="file.objectURL" width="90" height="50" />
                      <span class="font-semibold text-xs">{{ file.name }}</span>
                      <div class="text-xs">{{ formatSize(file.size) }}</div>
                      <Badge value="Pendiente" severity="warning" />
                      <Button icon="pi pi-times" @click="removeFileCallback(index)" outlined rounded severity="danger"
                        size="small" />
                    </div>
                  </div>
                </div>
              </template>

              <template #empty>
                <div class="flex items-center justify-center flex-col text-center">
                  <i class="pi pi-cloud-upload border-2 rounded-full p-4 text-4xl text-400 border-400" />
                  <p class="mt-2 mb-2 text-sm">Arrastra y suelta archivos aquí para cargar las evidencias.</p>
                  <p class="text-xs text-gray-600">JPG, PNG, PDF (máx. 5MB)</p>
                </div>
              </template>
            </FileUpload>
          </div>
        </div>

        <!-- Evidencia PEP -->
        <div>
          <h3 class="text-base font-semibold mb-2 text-gray-900 flex items-center gap-2">
            <i class="pi pi-upload text-purple-600"></i>
            Evidencia PEP
          </h3>

          <!-- Listado SIEMPRE visible -->
          <div class="space-y-2 mb-3">
            <template v-if="pepEvidences.length">
              <div v-for="item in pepEvidences" :key="'pep-' + item.id"
                class="bg-purple-50 p-3 rounded-md border border-purple-200 flex items-center justify-between">
                <div class="flex items-center gap-2 truncate">
                  <i class="pi pi-file"></i>
                  <div class="truncate">
                    <div class="text-xs font-medium text-purple-800 truncate">{{ item.name }}</div>
                    <div class="text-[11px] text-purple-700/80">{{ item.mime }} • {{ formatSize(item.size) }}</div>
                  </div>
                </div>
                <div class="flex gap-2 shrink-0">
                  <Button label="Ver" icon="pi pi-external-link" size="small" outlined
                    @click="viewDocument(toViewUrl(item.url))" />
                  <Button icon="pi pi-trash" size="small" outlined severity="danger"
                    @click="deletePepEvidence(item.id)" />
                </div>
              </div>
            </template>
            <template v-else>
              <div class="bg-purple-50 p-3 rounded-md border border-purple-200 text-xs text-purple-900">
                Aún no hay evidencias PEP.
              </div>
            </template>
          </div>

          <!-- Uploader (multi) -->
          <div class="bg-gray-50 p-3 rounded-md border">
            <FileUpload ref="pepFileUploader" mode="advanced" name="file" accept=".jpg,.jpeg,.png,.pdf"
              :maxFileSize="5120000" :multiple="true" :auto="true" :customUpload="true"
              @uploader="uploadPepEvidenceFile" @select="onFileSelect" :showUploadButton="false"
              :showCancelButton="false">
              <template #header="{ chooseCallback, clearCallback, files }">
                <div class="flex justify-between items-center w-full gap-2">
                  <div class="flex gap-2">
                    <Button @click="chooseCallback()" icon="pi pi-plus" rounded outlined size="small" />
                    <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger" size="small"
                      :disabled="!files || files.length === 0" />
                  </div>
                </div>
              </template>

              <template #content="{ files, removeFileCallback }">
                <div v-if="files.length > 0">
                  <div class="flex flex-wrap p-0 gap-3">
                    <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                      class="card m-0 px-4 py-2 flex flex-col border-1 surface-border items-center gap-2 text-center">
                      <img role="presentation" :alt="file.name" :src="file.objectURL" width="90" height="50" />
                      <span class="font-semibold text-xs">{{ file.name }}</span>
                      <div class="text-xs">{{ formatSize(file.size) }}</div>
                      <Badge value="Pendiente" severity="warning" />
                      <Button icon="pi pi-times" @click="removeFileCallback(index)" outlined rounded severity="danger"
                        size="small" />
                    </div>
                  </div>
                </div>
              </template>

              <template #empty>
                <div class="flex items-center justify-center flex-col text-center">
                  <i class="pi pi-cloud-upload border-2 rounded-full p-4 text-4xl text-400 border-400" />
                  <p class="mt-2 mb-2 text-sm">Arrastra y suelta archivos aquí para cargar evidencias PEP.</p>
                  <p class="text-xs text-gray-600">JPG, PNG, PDF (máx. 5MB)</p>
                </div>
              </template>
            </FileUpload>
          </div>
        </div>
      </div>

      <!-- Comentarios -->
      <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>
          <h3 class="text-base font-semibold mb-2 text-gray-900 flex items-center gap-2">
            <i class="pi pi-comment text-blue-600"></i>
            Comentarios de Validación
          </h3>

          <div class="space-y-3 mb-3">
            <div v-if="editableInvestor.approval1_comment" class="bg-blue-50 p-3 rounded-md border border-blue-200">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-blue-800">Comentario de Primera Validación</span>
                <span class="text-[11px] text-gray-500">{{ formatDate(editableInvestor.approval1_at) }}</span>
              </div>
              <p class="text-gray-800 text-sm">{{ editableInvestor.approval1_comment }}</p>
            </div>

            <div v-if="editableInvestor.approval2_comment" class="bg-green-50 p-3 rounded-md border border-green-200">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-green-800">Comentario de Segunda Validación</span>
                <span class="text-[11px] text-gray-500">{{ formatDate(editableInvestor.approval2_at) }}</span>
              </div>
              <p class="text-gray-800 text-sm">{{ editableInvestor.approval2_comment }}</p>
            </div>

            <div v-if="isValidated && !editableInvestor.approval1_comment && !editableInvestor.approval2_comment"
              class="bg-gray-50 p-3 rounded-md border border-gray-200 text-center">
              <i class="pi pi-info-circle text-xl text-gray-400 mb-1"></i>
              <p class="text-gray-600 text-sm">No hay comentarios registrados</p>
            </div>
          </div>

          <div class="mb-3" v-if="!isValidated && !hasFirstValidationComplete">
            <div class="bg-gray-50 p-3 rounded-md border">
              <label class="block text-xs font-medium mb-2 text-gray-900">Comentario de Primera Validación</label>
              <Textarea v-model="commentText" rows="3" class="w-full mb-2" placeholder="Ingrese su comentario..."
                :disabled="loading" />
              <div class="flex gap-2">
                <Button label="Guardar Comentario" icon="pi pi-save" size="small" severity="info" @click="saveComment"
                  :loading="loading" :disabled="!commentText.trim()" />
                <Button label="Observar" icon="pi pi-exclamation-triangle" size="small" severity="warn"
                  @click="showObserveDialog()" :disabled="loading" />
              </div>
            </div>
          </div>

          <div class="mb-3" v-if="!isValidated && canShowSecondValidation">
            <div class="bg-gray-50 p-3 rounded-md border">
              <label class="block text-xs font-medium mb-2 text-gray-900">Comentario de Segunda Validación</label>
              <Textarea v-model="comment2Text" rows="3" class="w-full mb-2"
                placeholder="Ingrese su comentario para la segunda validación..." :disabled="loading" />
              <Button label="Guardar Comentario 2da" icon="pi pi-save" size="small" severity="info"
                @click="saveComment2" :loading="loading" :disabled="!comment2Text.trim()" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-wrap gap-2 justify-end">
        <template v-if="!isValidated">
          <template v-if="!hasFirstValidationComplete || editableInvestor.approval1_status === 'observed'">
            <Button label="Aprobar" icon="pi pi-check" size="small" severity="success"
              @click="showConfirmDialog('approve')" :disabled="loading" />
            <Button label="Rechazar" icon="pi pi-times" size="small" severity="danger"
              @click="showConfirmDialog('reject')" :disabled="loading" />
          </template>

          <template v-if="canShowSecondValidation">
            <Button label="Aprobar Final" icon="pi pi-check" size="small" severity="success"
              @click="showConfirmDialog('approve2')" :disabled="loading" />
            <Button label="Rechazar Final" icon="pi pi-times" size="small" severity="danger"
              @click="showConfirmDialog('reject2')" :disabled="loading" />
          </template>

          <div v-if="editableInvestor.approval1_status === 'approved' && spectroEvidences.length === 0"
            class="flex items-center gap-2 mr-2 px-2 py-1 bg-yellow-50 rounded border border-yellow-300">
            <i class="pi pi-exclamation-triangle text-yellow-600 text-sm"></i>
            <span class="text-yellow-800 text-xs font-medium">Falta evidencia Espectro para validación final</span>
          </div>
        </template>

        <div v-if="isValidated" class="flex items-center gap-2 mr-2">
          <i class="pi pi-check-circle text-green-600 text-lg"></i>
          <span class="text-green-700 text-sm font-medium">Inversionista Validado</span>
        </div>

        <Button label="Cerrar" size="small" severity="secondary" text @click="closeDialog" :disabled="loading" />
      </div>
    </template>
  </Dialog>

  <!-- Dialog de Confirmación -->
  <Dialog v-model:visible="showConfirmationDialog" :style="{ width: '440px' }" :header="confirmationTitle"
    :modal="true">
    <div class="flex items-center gap-3 mb-3">
      <i :class="confirmationIcon" :style="{ fontSize: '1.5rem' }"></i>
      <span class="text-sm">{{ confirmationMessage }}</span>
    </div>

    <div v-if="actionType === 'reject' || actionType === 'reject2'" class="mb-2">
      <label class="block text-xs font-medium mb-2">Motivo del Rechazo *</label>
      <Textarea v-model="rejectionReason" rows="3" class="w-full" placeholder="Explique el motivo del rechazo..."
        required />
      <small class="text-red-500">Este campo es obligatorio</small>
    </div>

    <template #footer>
      <Button label="Cancelar" size="small" severity="secondary" text @click="closeConfirmDialog" :disabled="loading" />
      <Button :label="confirmButtonLabel" size="small" :severity="confirmButtonSeverity" @click="executeAction"
        :loading="loading"
        :disabled="(actionType === 'reject' || actionType === 'reject2') && !rejectionReason.trim()" />
    </template>
  </Dialog>

  <!-- Dialog de Observación -->
  <Dialog v-model:visible="showObservationDialog" :style="{ width: '440px' }" :header="observeTitle" :modal="true">
    <div class="mb-2">
      <p class="text-gray-700 text-sm mb-3">
        Al observar, se enviará una notificación por email con el comentario para que el inversionista pueda actualizar
        sus datos.
      </p>
      <label class="block text-xs font-medium mb-2">Comentario de Observación *</label>
      <Textarea v-model="observationComment" rows="3" class="w-full"
        placeholder="Describa qué necesita ser corregido o actualizado..." required />
      <small class="text-orange-600">Este comentario será enviado al inversionista por email.</small>
    </div>

    <template #footer>
      <Button label="Cancelar" size="small" severity="secondary" text @click="closeObserveDialog" :disabled="loading" />
      <Button label="Observar y Notificar" size="small" severity="warn" icon="pi pi-send" @click="executeObservation"
        :loading="loading" :disabled="!observationComment.trim()" />
    </template>
  </Dialog>

  <Toast />
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import Badge from 'primevue/badge';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';


// === Ubigeo name resolvers (code -> name) ===
const departmentName = ref<string>('')
const provinceName = ref<string>('')
const districtName = ref<string>('')


const NOVALINK = import.meta.env.VITE_NOVALINK_URL

const isCodeLike = (v?: string) => {
  if (!v) return false
  // Typical ubigeo codes are numeric strings; adjust if yours differ
  return /^[0-9]+$/.test(v)
}

async function resolveUbigeoNames() {
  try {
    const depCode = editableInvestor.value.department
    const provCode = editableInvestor.value.province
    const distCode = editableInvestor.value.district

    // If any are already names (not numeric), keep them
    if (!isCodeLike(depCode)) departmentName.value = depCode || departmentName.value
    if (!isCodeLike(provCode)) provinceName.value = provCode || provinceName.value
    if (!isCodeLike(distCode)) districtName.value = distCode || districtName.value

    // Resolve department name
    if (isCodeLike(depCode)) {
      const { data: deps } = await axios.get(`${NOVALINK}/api/v1/peru/ubigeo`)
      const dep = deps?.find((d: any) => d.ubigeo_code === depCode)
      if (dep) departmentName.value = dep.ubigeo_name
    }

    // Resolve province name
    if (isCodeLike(depCode) && isCodeLike(provCode)) {
      const { data: provs } = await axios.get(`${NOVALINK}/api/v1/peru/ubigeo/${depCode}`)
      const p = provs?.provinces?.find((x: any) => x.ubigeo_code === provCode)
      if (p) provinceName.value = p.ubigeo_name
    }

    // Resolve district name
    if (isCodeLike(depCode) && isCodeLike(provCode) && isCodeLike(distCode)) {
      const { data: dists } = await axios.get(`${NOVALINK}/api/v1/peru/ubigeo/${depCode}/${provCode}`)
      const d = dists?.districts?.find((x: any) => x.ubigeo_code === distCode)
      if (d) districtName.value = d.ubigeo_name
    }
  } catch {
    // Silent fail; keep original values
  }
}

// Re-resolve when the investor prop changes


const toast = useToast();

interface Investor {
  id: string;
  name: string;
  first_last_name?: string;
  second_last_name?: string;
  document: string;
  alias?: string;
  telephone: string;
  email: string;
  status: string;
  created_at?: string;
  document_front?: string;
  document_back?: string;
  investor_photo_path?: string;
  department?: string;
  province?: string;
  district?: string;
  address?: string;
  personaexpuesta?: boolean;
  relacionPolitica?: boolean;
  emailverificacion?: string;
  type?: string;
  codigo?: string;
  asignado?: number;
  approval1_status: string;
  approval1_by?: string;
  approval1_comment?: string;
  approval1_at?: string;
  approval2_status?: string;
  approval2_by?: string;
  approval2_comment?: string;
  approval2_at?: string;
  perfil?: string;
  creacion?: string;
}

interface Evidence {
  id: number;
  name: string;
  mime: string;
  size: number;
  url: string;
  download_url: string;
  created_at?: string;
}

const props = defineProps<{
  investor: Investor;
  visible: boolean;
  currentUserId?: string;
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
  'status-updated': [investor: Investor];
}>();

// ==== URL normalizer for absolute paths or S3 keys -> proxy /s3/... (robusta) =====
// ==== URL normalizer: absolute MinIO or local paths -> proxy /s3/... =====
const toViewUrl = (raw?: string | null) => {
  if (!raw) return '';

  // normalize slashes and trim
  const norm = String(raw).replace(/\\/g, '/').trim();

  // already proxied
  if (norm.startsWith('/s3/')) return norm;

  // 1) If the string already contains the key starting at investors|inversores, use that
  const keyMatch = norm.match(/(investors|inversores)\/.+$/i);
  if (keyMatch) {
    const key = keyMatch[0].replace(/^\/+/, '');
    return `/s3/${key}`;
  }

  // 2) If it's an absolute URL (MinIO public URL etc.), strip host & optional bucket,
  //    then extract the key after investors|inversores
  try {
    const u = new URL(norm);
    const path = u.pathname.replace(/^\/+/, ''); // e.g. "mi-bucket/inversores/documentos/xyz.jpg"
    // Optional leading bucket segment, then capture the key from investors|inversores onward
    const m2 = path.match(/(?:^[^/]+\/)?((?:investors|inversores)\/.+)$/i);
    if (m2 && m2[1]) {
      return `/s3/${m2[1]}`;
    }
  } catch {
    // not an absolute URL — ignore
  }

  // 3) As a last resort, treat whatever we have as a key
  return `/s3/${norm.replace(/^\/+/, '')}`;
};

// Helpers para previsualización segura
const isImageLike = (url: string) => /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test((url || '').split('?')[0] || '');
const safeImgOnError = (e: Event) => {
  const el = e.target as HTMLImageElement;
  el.onerror = null;
  el.src =
    'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-family="sans-serif" font-size="14">No se pudo cargar la imagen</text></svg>';
};

// Refs
const fileUploader = ref();
const pepFileUploader = ref();

// Lists
const spectroEvidences = ref<Evidence[]>([]);
const pepEvidences = ref<Evidence[]>([]);

// Editable investor
const editableInvestor = ref<Investor>({ ...props.investor });

watch(
  () => editableInvestor.value,
  (inv) => {
    departmentName.value = inv?.department ?? ''
    provinceName.value = inv?.province ?? ''
    districtName.value = inv?.district ?? ''
    resolveUbigeoNames()
  },
  { immediate: true, deep: true }
)


watch(
  () => props.investor,
  (newInvestor) => {
    editableInvestor.value = { ...newInvestor };
  },
  { deep: true }
);

const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

// Dialog states
const showConfirmationDialog = ref(false);
const showObservationDialog = ref(false);

// Forms
const rejectionReason = ref('');
const commentText = ref('');
const comment2Text = ref('');
const observationComment = ref('');
const selectedFile = ref<File | null>(null);

// Controls
const loading = ref(false);
const actionType = ref<'approve' | 'reject' | 'approve2' | 'reject2' | ''>('');

// Validation states
const isValidated = computed(() => {
  return editableInvestor.value.status === 'validated' || editableInvestor.value.status === 'Validado';
});

const hasFirstValidationComplete = computed(() => {
  return (
    editableInvestor.value.approval1_status === 'approved' ||
    editableInvestor.value.approval1_status === 'rejected' ||
    editableInvestor.value.approval1_status === 'observed'
  );
});

const canShowSecondValidation = computed(() => {
  return editableInvestor.value.approval1_status === 'approved';
});

// Confirm dialog derived state
const confirmationTitle = computed(() => {
  switch (actionType.value) {
    case 'approve': return 'Confirmar Primera Aprobación';
    case 'reject': return 'Confirmar Primera Rechazo';
    case 'approve2': return 'Confirmar Aprobación Final';
    case 'reject2': return 'Confirmar Rechazo Final';
    default: return 'Confirmar Acción';
  }
});

const confirmationMessage = computed(() => {
  switch (actionType.value) {
    case 'approve': return '¿Está seguro que desea aprobar esta primera validación?';
    case 'reject': return '¿Está seguro que desea rechazar esta primera validación?';
    case 'approve2': return '¿Está seguro que desea aprobar esta validación final? Esto completará el proceso.';
    case 'reject2': return '¿Está seguro que desea rechazar esta validación final?';
    default: return '¿Está seguro de esta acción?';
  }
});

const confirmationIcon = computed(() => {
  switch (actionType.value) {
    case 'approve':
    case 'approve2': return 'pi pi-check-circle text-green-500';
    case 'reject':
    case 'reject2': return 'pi pi-exclamation-triangle text-red-500';
    default: return 'pi pi-question-circle text-blue-500';
  }
});

const confirmButtonLabel = computed(() => {
  switch (actionType.value) {
    case 'approve': return 'Aprobar Primera';
    case 'reject': return 'Rechazar Primera';
    case 'approve2': return 'Aprobar Final';
    case 'reject2': return 'Rechazar Final';
    default: return 'Confirmar';
  }
});

const confirmButtonSeverity = computed(() => {
  switch (actionType.value) {
    case 'approve':
    case 'approve2': return 'success';
    case 'reject':
    case 'reject2': return 'danger';
    default: return 'info';
  }
});

// Utils
const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'approved': return 'success';
    case 'rejected': return 'danger';
    case 'observed': return 'warn';
    case 'pending': return 'info';
    case 'validated':
    case 'Validado': return 'success';
    default: return 'secondary';
  }
};

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'approved': return 'Aprobado';
    case 'rejected': return 'Rechazado';
    case 'observed': return 'Observado';
    case 'pending': return 'Pendiente';
    case 'validated':
    case 'Validado': return 'Validado';
    case 'not validated': return 'No Validado';
    default: return status;
  }
};

const getAvatarDisplay = (perfil: string | undefined | null) => {
  if (!perfil || perfil === 'Sin perfil' || perfil === null) return 'No';
  return perfil;
};

const formatDate = (date: string | null) => {
  if (!date) return 'No disponible';
  return date;
};

const formatSize = (bytes: number) => {
  const k = 1024;
  const dm = 3;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  if (bytes === 0) return '0 B';
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// ======== Observación contextual ========
type ObserveTarget = 'dni_front' | 'dni_back' | 'investor_photo' | null;
const observeTarget = ref<ObserveTarget>(null);

const observeTitle = computed(() => {
  switch (observeTarget.value) {
    case 'dni_front': return 'Observar — DNI - Parte Frontal';
    case 'dni_back': return 'Observar — DNI - Parte Posterior';
    case 'investor_photo': return 'Observar — Foto del Inversionista';
    default: return 'Observar Inversionista';
  }
});

// Dialog open/close helpers
const showConfirmDialog = (action: 'approve' | 'reject' | 'approve2' | 'reject2') => {
  actionType.value = action;
  rejectionReason.value = '';
  showConfirmationDialog.value = true;
};

const showObserveDialog = (target?: ObserveTarget) => {
  observeTarget.value = target ?? null;
  observationComment.value = '';
  showObservationDialog.value = true;
};

// File handling
const onFileSelect = (event: any) => {
  selectedFile.value = event.files?.[0] || null;
  if (event.files?.[0]) {
    toast.add({
      severity: 'info',
      summary: 'Archivo Seleccionado',
      detail: `${event.files[0].name} está listo para subir`,
      life: 3000,
    });
  }
};

// Actions
const executeAction = async () => {
  loading.value = true;
  try {
    let response;

    switch (actionType.value) {
      case 'approve':
        response = await axios.put(`/investor/${editableInvestor.value.id}/aprobar-primera`);
        break;
      case 'reject':
        response = await axios.put(`/investor/${editableInvestor.value.id}/rechazar-primera`, {
          approval1_comment: rejectionReason.value,
        });
        break;
      case 'approve2':
        response = await axios.put(`/investor/${editableInvestor.value.id}/aprobar-segunda`);
        break;
      case 'reject2':
        response = await axios.put(`/investor/${editableInvestor.value.id}/rechazar-segunda`, {
          approval2_comment: rejectionReason.value,
        });
        break;
    }

    if (response) {
      toast.add({ severity: 'success', summary: 'Éxito', detail: response.data.message, life: 3000 });
      editableInvestor.value = { ...response.data.data };
      showConfirmationDialog.value = false;
      actionType.value = '';
      rejectionReason.value = '';
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al procesar la acción',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const executeObservation = async () => {
  loading.value = true;
  try {
    const base = `/investor/${editableInvestor.value.id}`;

    const labelByTarget: Record<NonNullable<ObserveTarget>, string> = {
      dni_front: '"DNI PARTE FRONTAL" OBSERVADA, ',
      dni_back: '"DNI PARTE POSTERIOR" OBSERVADA, ',
      investor_photo: '"FOTO" OBSERVADA, ',
    };

    let endpoint = `${base}/observaciones`;
    let finalComment = observationComment.value?.trim() ?? '';

    if (observeTarget.value) {
      if (observeTarget.value === 'dni_front') endpoint = `${base}/observar-dni-frontal`;
      if (observeTarget.value === 'dni_back') endpoint = `${base}/observar-dni-posterior`;
      if (observeTarget.value === 'investor_photo') endpoint = `${base}/observar-foto`;
      finalComment = `${labelByTarget[observeTarget.value]}${finalComment}`;
    }

    const response = await axios.put(endpoint, {
      approval1_comment: finalComment,
    });

    toast.add({ severity: 'success', summary: 'Éxito', detail: response.data.message, life: 3000 });
    editableInvestor.value = { ...response.data.data };
    showObservationDialog.value = false;
    observationComment.value = '';
    observeTarget.value = null;
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al observar',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// ====== Evidence lists (load/refresh/delete) ======
// ====== Evidence lists (load/refresh/delete) ======
const sortNewest = (arr: Evidence[]) =>
  [...arr].sort((a, b) => {
    const ta = a.created_at ? Date.parse(a.created_at) : 0;
    const tb = b.created_at ? Date.parse(b.created_at) : 0; // <- aquí estaba el typo
    if (tb !== ta) return tb - ta;
    return (b.id || 0) - (a.id || 0);
  });


const loadSpectroEvidences = async () => {
  try {
    const { data } = await axios.get(`/investor/${editableInvestor.value.id}/evidencias-spectro`);
    if (data?.success) spectroEvidences.value = sortNewest(data.data || []);
  } catch (e) {/* noop */ }
};

const loadPepEvidences = async () => {
  try {
    const { data } = await axios.get(`/investor/${editableInvestor.value.id}/evidencias-pep`);
    if (data?.success) pepEvidences.value = sortNewest(data.data || []);
  } catch (e) {/* noop */ }
};

// Load lists when dialog opens, and also on mount if already visible
watch(
  () => visible.value,
  async (v) => {
    if (v && editableInvestor.value?.id) {
      await Promise.all([loadSpectroEvidences(), loadPepEvidences()]);
    }
  }
);

onMounted(async () => {
  if (visible.value && editableInvestor.value?.id) {
    await Promise.all([loadSpectroEvidences(), loadPepEvidences()]);
  }
});

// Evidence Espectro (multi)
const uploadEvidenceFile = async (event: any) => {
  if (!event.files || event.files.length === 0) return;
  loading.value = true;
  try {
    for (const file of event.files) {
      const formData = new FormData();
      formData.append('file', file);
      await axios.post(
        `/investor/${editableInvestor.value.id}/adjuntar-evidencia-spectro`,
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    }
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Evidencias Espectro subidas.', life: 2500 });
    await loadSpectroEvidences();
    fileUploader.value?.clear?.();
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir evidencias Espectro',
      life: 3500,
    });
  } finally {
    loading.value = false;
  }
};

const deleteSpectroEvidence = async (evidenceId: number) => {
  loading.value = true;
  try {
    await axios.delete(`/investor/${editableInvestor.value.id}/evidencias-spectro/${evidenceId}`);
    await loadSpectroEvidences();
    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Evidencia Espectro eliminada.', life: 2200 });
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo eliminar.', life: 3500 });
  } finally {
    loading.value = false;
  }
};

// Evidence PEP (multi)
const uploadPepEvidenceFile = async (event: any) => {
  if (!event.files || event.files.length === 0) return;
  loading.value = true;
  try {
    for (const file of event.files) {
      const formData = new FormData();
      formData.append('file', file);
      await axios.post(
        `/investor/${editableInvestor.value.id}/adjuntar-evidencia-pep`,
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    }
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Evidencias PEP subidas.', life: 2500 });
    await loadPepEvidences();
    pepFileUploader.value?.clear?.();
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir evidencias PEP',
      life: 3500,
    });
  } finally {
    loading.value = false;
  }
};

const deletePepEvidence = async (evidenceId: number) => {
  loading.value = true;
  try {
    await axios.delete(`/investor/${editableInvestor.value.id}/evidencias-pep/${evidenceId}`);
    await loadPepEvidences();
    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Evidencia PEP eliminada.', life: 2200 });
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo eliminar.', life: 3500 });
  } finally {
    loading.value = false;
  }
};

// Close helpers
const closeDialog = () => {
  showConfirmationDialog.value = false;
  showObservationDialog.value = false;
  actionType.value = '';
  rejectionReason.value = '';
  observationComment.value = '';
  commentText.value = '';
  comment2Text.value = '';
  observeTarget.value = null;
  loading.value = false;
};

const closeConfirmDialog = () => {
  showConfirmationDialog.value = false;
  actionType.value = '';
  rejectionReason.value = '';
};

const closeObserveDialog = () => {
  showObservationDialog.value = false;
  observationComment.value = '';
  observeTarget.value = null;
};

// Comments
const saveComment = async () => {
  loading.value = true;
  try {
    const response = await axios.put(`/investor/${editableInvestor.value.id}/comentar-primera`, {
      approval1_comment: commentText.value,
    });
    toast.add({ severity: 'success', summary: 'Éxito', detail: response.data.message, life: 3000 });
    editableInvestor.value = { ...response.data.data };
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al guardar el comentario',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const saveComment2 = async () => {
  loading.value = true;
  try {
    const response = await axios.put(`/investor/${editableInvestor.value.id}/comentar-segunda`, {
      approval2_comment: comment2Text.value,
    });
    toast.add({ severity: 'success', summary: 'Éxito', detail: response.data.message, life: 3000 });
    editableInvestor.value = { ...response.data.data };
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al guardar el comentario de segunda validación',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const viewDocument = (url: string) => window.open(url, '_blank');
</script>
<template>
  <Dialog v-model:visible="visible" :style="{ width: '90vw', maxWidth: '1200px' }" header="Validación de Inversionista"
    :modal="true" :closable="true" @hide="closeDialog">
    <div>
      <h3 class="text-lg font-bold mb-3 text-gray-900">Información Básica</h3>
            <Message severity="info" :closable="false">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div><span class="text-sm font-medium text-blue-800">Nombre:</span>
            <p>{{ investor.name }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">DNI:</span>
            <p>{{ investor.document }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Teléfono:</span>
            <p>{{ investor.telephone }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Email:</span>
            <p>{{ investor.email }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Validado por IA:</span>
            <p>No</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Estado:</span>
            <Tag :severity="getStatusSeverity(investor.status)" :value="getStatusLabel(investor.status)" />
          </div>
          <div><span class="text-sm font-medium text-blue-800">Persona Expuesta:</span>
            <p>{{ investor.personaexpuesta ? 'Sí' : 'No' }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Relación con PEP:</span>
            <p>{{ investor.relacionPolitica ? 'Sí' : 'No' }}</p>
          </div>
        </div>
      </Message>
      <!-- Documentos DNI -->
<div class="mb-6 mt-6">
  <h3 class="text-lg font-bold mb-3 text-gray-900 flex items-center gap-2">
    <i class="pi pi-id-card text-blue-600"></i>
    Documentos de Identidad
  </h3>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- DNI Frontal -->
    <div class="border rounded-lg overflow-hidden">
      <div class="bg-gray-100 px-3 py-2 border-b">
        <span class="text-sm font-medium text-gray-900">DNI - Parte Frontal</span>
      </div>
      <div class="p-2">
        <!-- Imagen existente -->
        <div v-if="editableInvestor.document_front">
          <img :src="editableInvestor.document_front" alt="DNI Frontal"
            class="w-full h-48 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
            @click="viewDocument(editableInvestor.document_front)" />
          <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2 mb-2"
            @click="viewDocument(editableInvestor.document_front)" />
          
          <!-- Botón de subir nuevo (solo si está observado) -->
          <Button v-if="editableInvestor.approval1_status === 'observed'" 
            label="Subir nuevo" icon="pi pi-upload" size="small" severity="warn" 
            class="w-full" @click="showDocumentFrontUploader = true" />
        </div>

        <!-- Estado sin documento -->
        <div v-else class="text-center py-8 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
          <i class="pi pi-image text-3xl text-gray-400 mb-2"></i>
          <p class="text-gray-600 text-sm">No hay documento frontal</p>
        </div>

        <!-- Uploader para documento frontal -->
        <div v-if="showDocumentFrontUploader" class="mt-3 bg-blue-50 p-3 rounded border">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-blue-800">Subir nuevo DNI frontal</span>
            <Button icon="pi pi-times" size="small" text @click="showDocumentFrontUploader = false" />
          </div>
          
          <FileUpload ref="documentFrontUploader" mode="advanced" name="document_front" 
            accept=".jpg,.jpeg,.png" :maxFileSize="5120000" :multiple="false" :auto="true" 
            :customUpload="true" @uploader="uploadDocumentFront" @select="onFileSelect" 
            :showUploadButton="false" :showCancelButton="false">
            
            <template #header="{ chooseCallback, clearCallback, files }">
              <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                <div class="flex gap-2">
                  <Button @click="chooseCallback()" icon="pi pi-plus" rounded outlined size="small"></Button>
                  <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger" size="small"
                    :disabled="!files || files.length === 0"></Button>
                </div>
              </div>
            </template>
            
            <template #content="{ files, removeFileCallback }">
              <div v-if="files.length > 0">
                <div class="flex flex-wrap gap-2">
                  <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                    class="card m-0 p-2 flex flex-column border-1 surface-border align-items-center gap-2 text-center">
                    <img role="presentation" :alt="file.name" :src="file.objectURL" width="60" height="40" />
                    <span class="font-semibold text-xs">{{ file.name }}</span>
                    <Badge value="Listo" severity="info" />
                    <Button icon="pi pi-times" @click="removeFileCallback(index)" outlined rounded
                      severity="danger" size="small" />
                  </div>
                </div>
              </div>
            </template>
            
            <template #empty>
              <div class="flex align-items-center justify-content-center flex-column">
                <i class="pi pi-cloud-upload border-2 border-circle p-3 text-4xl text-400 border-400" />
                <p class="mt-2 mb-2 text-sm">Arrastra el nuevo DNI frontal aquí</p>
                <p class="text-xs text-gray-600">JPG, PNG (máx. 5MB)</p>
              </div>
            </template>
          </FileUpload>
        </div>
      </div>
    </div>

    <!-- DNI Posterior -->
    <div class="border rounded-lg overflow-hidden">
      <div class="bg-gray-100 px-3 py-2 border-b">
        <span class="text-sm font-medium text-gray-900">DNI - Parte Posterior</span>
      </div>
      <div class="p-2">
        <!-- Imagen existente -->
        <div v-if="editableInvestor.document_back">
          <img :src="editableInvestor.document_back" alt="DNI Posterior"
            class="w-full h-48 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
            @click="viewDocument(editableInvestor.document_back)" />
          <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2 mb-2"
            @click="viewDocument(editableInvestor.document_back)" />
          
          <!-- Botón de subir nuevo (solo si está observado) -->
          <Button v-if="editableInvestor.approval1_status === 'observed'" 
            label="Subir nuevo" icon="pi pi-upload" size="small" severity="warn" 
            class="w-full" @click="showDocumentBackUploader = true" />
        </div>

        <!-- Estado sin documento -->
        <div v-else class="text-center py-8 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
          <i class="pi pi-image text-3xl text-gray-400 mb-2"></i>
          <p class="text-gray-600 text-sm">No hay documento posterior</p>
        </div>

        <!-- Uploader para documento posterior -->
        <div v-if="showDocumentBackUploader" class="mt-3 bg-blue-50 p-3 rounded border">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-blue-800">Subir nuevo DNI posterior</span>
            <Button icon="pi pi-times" size="small" text @click="showDocumentBackUploader = false" />
          </div>
          
          <FileUpload ref="documentBackUploader" mode="advanced" name="document_back" 
            accept=".jpg,.jpeg,.png" :maxFileSize="5120000" :multiple="false" :auto="true" 
            :customUpload="true" @uploader="uploadDocumentBack" @select="onFileSelect" 
            :showUploadButton="false" :showCancelButton="false">
            
            <template #header="{ chooseCallback, clearCallback, files }">
              <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                <div class="flex gap-2">
                  <Button @click="chooseCallback()" icon="pi pi-plus" rounded outlined size="small"></Button>
                  <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger" size="small"
                    :disabled="!files || files.length === 0"></Button>
                </div>
              </div>
            </template>
            
            <template #content="{ files, removeFileCallback }">
              <div v-if="files.length > 0">
                <div class="flex flex-wrap gap-2">
                  <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                    class="card m-0 p-2 flex flex-column border-1 surface-border align-items-center gap-2 text-center">
                    <img role="presentation" :alt="file.name" :src="file.objectURL" width="60" height="40" />
                    <span class="font-semibold text-xs">{{ file.name }}</span>
                    <Badge value="Listo" severity="info" />
                    <Button icon="pi pi-times" @click="removeFileCallback(index)" outlined rounded
                      severity="danger" size="small" />
                  </div>
                </div>
              </div>
            </template>
            
            <template #empty>
              <div class="flex align-items-center justify-content-center flex-column">
                <i class="pi pi-cloud-upload border-2 border-circle p-3 text-4xl text-400 border-400" />
                <p class="mt-2 mb-2 text-sm">Arrastra el nuevo DNI posterior aquí</p>
                <p class="text-xs text-gray-600">JPG, PNG (máx. 5MB)</p>
              </div>
            </template>
          </FileUpload>
        </div>
      </div>
    </div>

    <!-- Foto del Inversionista -->
    <div class="border rounded-lg overflow-hidden">
      <div class="bg-gray-100 px-3 py-2 border-b">
        <span class="text-sm font-medium text-gray-900">Foto del Inversionista</span>
      </div>
      <div class="p-2">
        <!-- Imagen existente -->
        <div v-if="editableInvestor.investor_photo_path">
          <img :src="editableInvestor.investor_photo_path" alt="Foto del Inversionista"
            class="w-full h-48 object-contain cursor-pointer hover:opacity-90 transition-opacity border rounded"
            @click="viewDocument(editableInvestor.investor_photo_path)" />
          <Button label="Ver completo" icon="pi pi-eye" size="small" outlined class="w-full mt-2 mb-2"
            @click="viewDocument(editableInvestor.investor_photo_path)" />
          
        </div>

        <!-- Estado sin foto -->
        <div v-else class="text-center py-8 bg-gray-50 border-2 border-dashed border-gray-300 rounded">
          <i class="pi pi-user text-3xl text-gray-400 mb-2"></i>
          <p class="text-gray-600 text-sm">No hay foto del inversionista</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Alerta si no hay documentos -->
  <div v-if="!editableInvestor.document_front && !editableInvestor.document_back && !editableInvestor.investor_photo_path"
    class="text-center py-6 bg-yellow-50 border border-yellow-200 rounded-lg">
    <i class="pi pi-exclamation-triangle text-2xl text-yellow-600 mb-2"></i>
    <p class="text-gray-900 font-medium">No se han subido documentos de identidad</p>
  </div>
</div>
      <!-- Información adicional -->
      <h3 class="text-lg font-bold mb-3 text-gray-900 mt-6">Información Adicional</h3>
      <Message severity="secondary" :closable="false">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div><span class="text-sm font-medium text-blue-800">Avatar:</span>
            <p>{{ getAvatarDisplay(investor.perfil) }}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Departamento:</span><p>{{ investor.department }}</p></div>
          <div><span class="text-sm font-medium text-blue-800">Provincia:</span><p>{{ investor.province }}</p></div>
          <div><span class="text-sm font-medium text-blue-800">Distrito:</span><p>{{ investor.district }}</p></div>
          <div><span class="text-sm font-medium text-blue-800">Dirección:</span><p>{{ investor.address }}</p></div>
          <div><span class="text-sm font-medium text-blue-800">Fecha de registro:</span><p>{{ investor.creacion}}</p></div>
          <div><span class="text-sm font-medium text-blue-800">Email verificado:</span>
            <p>{{ investor.emailverificacion}}</p>
          </div>
          <div><span class="text-sm font-medium text-blue-800">Whatsapp Verificado:</span><p>No</p></div>
        </div>
      </Message>
      
      <!-- Sección de Evidencia Espectro y Comentarios lado a lado -->
      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Evidencia Espectro -->
        <div>
          <h3 class="text-lg font-bold mb-3 text-gray-900 flex items-center gap-2">
            <i class="pi pi-upload text-blue-600"></i>
            Evidencia Espectro
          </h3>
          
          <!-- Mostrar evidencia existente -->
          <div v-if="investor.file_path" class="mb-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="pi pi-file text-blue-600"></i>
                  <span class="text-sm font-medium text-blue-800">Evidencia adjuntada</span>
                </div>
                <div class="flex gap-2">
                  <Button label="Ver Archivo" icon="pi pi-external-link" size="small" outlined
                    @click="viewDocument(investor.file_path)" />
                  <!-- Solo mostrar el botón de cambiar archivo si no está validado -->
                  <Button v-if="!isValidated" label="Cambiar Archivo" icon="pi pi-refresh" size="small" severity="warn"
                    @click="showFileUploader = true" />
                </div>
              </div>
            </div>
          </div>

          <!-- Solo mostrar el mensaje de no evidencia si no está validado -->
          <div v-else-if="!isValidated" class="text-center py-6 bg-yellow-50 border border-yellow-200 rounded-lg">
            <i class="pi pi-exclamation-triangle text-2xl text-yellow-600 mb-2"></i>
            <p class="text-gray-900 font-medium">No se ha adjuntado evidencia</p>
          </div>

          <!-- Uploader de archivos (solo si no hay evidencia o se quiere cambiar y no está validado) -->
          <div v-if="!isValidated && (!investor.file_path || showFileUploader)" class="bg-gray-50 p-4 rounded-lg border">
            <div v-if="showFileUploader" class="flex justify-between items-center mb-3">
              <span class="text-sm font-medium text-gray-900">Subir nueva evidencia</span>
              <Button icon="pi pi-times" size="small" text @click="showFileUploader = false" />
            </div>
            
            <FileUpload ref="fileUploader" mode="advanced" name="file_path" accept=".jpg,.jpeg,.png,.pdf"
              :maxFileSize="5120000" :multiple="false" :auto="true" :customUpload="true" @uploader="uploadEvidenceFile"
              @select="onFileSelect" :showUploadButton="false" :showCancelButton="false">
              <template #header="{ chooseCallback, clearCallback, files }">
                <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                  <div class="flex gap-2">
                    <Button @click="chooseCallback()" icon="pi pi-plus" rounded outlined></Button>
                    <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger"
                      :disabled="!files || files.length === 0"></Button>
                  </div>
                </div>
              </template>
              <template #content="{ files, removeFileCallback }">
                <div v-if="files.length > 0">
                  <div class="flex flex-wrap p-0 sm:p-5 gap-5">
                    <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                      class="card m-0 px-6 flex flex-column border-1 surface-border align-items-center gap-3">
                      <div>
                        <img role="presentation" :alt="file.name" :src="file.objectURL" width="100" height="50" />
                      </div>
                      <span class="font-semibold">{{ file.name }}</span>
                      <div>{{ formatSize(file.size) }}</div>
                      <Badge value="Pending" severity="warning" />
                      <Button icon="pi pi-times" @click="removeFileCallback(index)" outlined rounded
                        severity="danger" />
                    </div>
                  </div>
                </div>
              </template>
              <template #empty>
                <div class="flex align-items-center justify-content-center flex-column">
                  <i class="pi pi-cloud-upload border-2 border-circle p-5 text-8xl text-400 border-400" />
                  <p class="mt-4 mb-4">Arrastra y suelta archivos aquí para cargar la evidencia.</p>
                  <p class="text-sm text-gray-600">Formatos permitidos: JPG, PNG, PDF (máx. 5MB)</p>
                </div>
              </template>
            </FileUpload>
          </div>
        </div>

        <!-- Comentarios inline -->
        <div>
          <h3 class="text-lg font-bold mb-3 text-gray-900 flex items-center gap-2">
            <i class="pi pi-comment text-blue-600"></i>
            Comentarios de Validación
          </h3>

          <!-- Comentarios existentes -->
          <div class="space-y-4 mb-4">
            <!-- Comentario de primera validación -->
            <div v-if="editableInvestor.approval1_comment" class="bg-blue-50 p-4 rounded-lg border border-blue-200">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-800">
                  Comentario de Primera Validación
                </span>
                <span class="text-xs text-gray-500">
                  {{ formatDate(editableInvestor.approval1_at) }}
                </span>
              </div>
              <p class="text-gray-800">{{ editableInvestor.approval1_comment }}</p>
            </div>

            <!-- Comentario de segunda validación -->
            <div v-if="editableInvestor.approval2_comment" class="bg-green-50 p-4 rounded-lg border border-green-200">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-green-800">
                  Comentario de Segunda Validación
                </span>
                <span class="text-xs text-gray-500">
                  {{ formatDate(editableInvestor.approval2_at) }}
                </span>
              </div>
              <p class="text-gray-800">{{ editableInvestor.approval2_comment }}</p>
            </div>

            <!-- Mensaje cuando no hay comentarios y está validado -->
            <div v-if="isValidated && !editableInvestor.approval1_comment && !editableInvestor.approval2_comment" 
              class="bg-gray-50 p-4 rounded-lg border border-gray-200">
              <div class="text-center">
                <i class="pi pi-info-circle text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">No hay comentarios registrados</p>
              </div>
            </div>
          </div>

          <!-- Formulario de comentario de primera validación (solo si no está validado) -->
          <div class="mb-4" v-if="!isValidated && !hasFirstValidationComplete">
            <div class="bg-gray-50 p-4 rounded-lg border">
              <label class="block text-sm font-medium mb-2 text-gray-900">
                Comentario de Primera Validación
              </label>
              <Textarea v-model="commentText" rows="3" class="w-full mb-3" placeholder="Ingrese su comentario..."
                :disabled="loading" />
              <div class="flex gap-2">
                <Button label="Guardar Comentario" icon="pi pi-save" size="small" severity="info" @click="saveComment"
                  :loading="loading" :disabled="!commentText.trim()" />
                <Button label="Observar" icon="pi pi-exclamation-triangle" size="small" severity="warn"
                  @click="showObserveDialog" :disabled="loading" />
              </div>
            </div>
          </div>

          <!-- Formulario de comentario de segunda validación (SOLO si primera fue APROBADA) -->
          <div class="mb-4" v-if="!isValidated && canShowSecondValidation">
            <div class="bg-gray-50 p-4 rounded-lg border">
              <label class="block text-sm font-medium mb-2 text-gray-900">
                Comentario de Segunda Validación
              </label>
              <Textarea v-model="comment2Text" rows="3" class="w-full mb-3"
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
        <!-- Solo mostrar botones de acción si no está validado -->
        <template v-if="!isValidated">
          <!-- Botones de primera validación (cuando no hay primera validación completa O está observado) -->
          <template v-if="!hasFirstValidationComplete || editableInvestor.approval1_status === 'observed'">
            <Button label="Aprobar" icon="pi pi-check" severity="success" @click="showConfirmDialog('approve')"
              :disabled="loading" />
            <Button label="Rechazar" icon="pi pi-times" severity="danger" @click="showConfirmDialog('reject')"
              :disabled="loading" />
          </template>

          <!-- Botones de segunda validación (SOLO si primera fue APROBADA Y hay evidencia Espectro) -->
          <template v-if="canShowSecondValidation">
            <Button label="Aprobar Final" icon="pi pi-check" severity="success" @click="showConfirmDialog('approve2')"
              :disabled="loading" />
            <Button label="Rechazar Final" icon="pi pi-times" severity="danger" @click="showConfirmDialog('reject2')"
              :disabled="loading" />
          </template>

          <!-- Mensaje informativo si primera fue aprobada pero falta evidencia -->
          <div v-if="editableInvestor.approval1_status === 'approved' && !editableInvestor.file_path" 
            class="flex items-center gap-2 mr-4 px-3 py-2 bg-yellow-50 rounded-lg border border-yellow-300">
            <i class="pi pi-exclamation-triangle text-yellow-600"></i>
            <span class="text-yellow-800 text-sm font-medium">Falta evidencia Espectro para validación final</span>
          </div>
        </template>

        <!-- Mostrar estado validado -->
        <div v-if="isValidated" class="flex items-center gap-2 mr-4">
          <i class="pi pi-check-circle text-green-600 text-xl"></i>
          <span class="text-green-700 font-medium">Inversionista Validado</span>
        </div>

        <Button label="Cerrar" severity="secondary" text @click="closeDialog" :disabled="loading" />
      </div>
    </template>
  </Dialog>

  <!-- Dialog de Confirmación -->
  <Dialog v-model:visible="showConfirmationDialog" :style="{ width: '500px' }" :header="confirmationTitle"
    :modal="true">
    <div class="flex align-items-center gap-3 mb-4">
      <i :class="confirmationIcon" :style="{ fontSize: '2rem' }"></i>
      <span>{{ confirmationMessage }}</span>
    </div>

    <div v-if="actionType === 'reject' || actionType === 'reject2'" class="mb-4">
      <label class="block text-sm font-medium mb-2">Motivo del Rechazo *</label>
      <Textarea v-model="rejectionReason" rows="4" class="w-full" placeholder="Explique el motivo del rechazo..."
        required />
      <small v-if="actionType === 'reject' || actionType === 'reject2'" class="text-red-500">
        Este campo es obligatorio
      </small>
    </div>

    <template #footer>
      <Button label="Cancelar" severity="secondary" text @click="closeConfirmDialog" :disabled="loading" />
      <Button :label="confirmButtonLabel" :severity="confirmButtonSeverity" @click="executeAction" :loading="loading"
        :disabled="(actionType === 'reject' || actionType === 'reject2') && !rejectionReason.trim()" />
    </template>
  </Dialog>

  <!-- Dialog de Observación -->
  <Dialog v-model:visible="showObservationDialog" :style="{ width: '500px' }" header="Observar Inversionista"
    :modal="true">
    <div class="mb-4">
      <p class="text-gray-700 mb-4">
        Al observar al inversionista, se le enviará una notificación por email con el comentario para que pueda
        actualizar
        sus datos.
      </p>
      <label class="block text-sm font-medium mb-2">Comentario de Observación *</label>
      <Textarea v-model="observationComment" rows="4" class="w-full"
        placeholder="Describa qué necesita ser corregido o actualizado..." required />
      <small class="text-orange-600">
        Este comentario será enviado al inversionista por email.
      </small>
    </div>

    <template #footer>
      <Button label="Cancelar" severity="secondary" text @click="closeObserveDialog" :disabled="loading" />
      <Button label="Observar y Notificar" severity="warn" icon="pi pi-send" @click="executeObservation"
        :loading="loading" :disabled="!observationComment.trim()" />
    </template>
  </Dialog>

  <!-- Toast para notificaciones -->
  <Toast />
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import Badge from 'primevue/badge';
import axios from 'axios';
import { useToast } from "primevue/usetoast";

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
  file_path?: string;
  perfil?: string;
  creacion?: string;
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

// Referencias
const fileUploader = ref();

// Estado para mostrar/ocultar uploaders
const showFileUploader = ref(false);
const showPhotoUploader = ref(false);
const showDocumentFrontUploader = ref(false);  // <-- FALTABA
const showDocumentBackUploader = ref(false);   // <-- FALTABA
const showInvestorPhotoUploader = ref(false);  // <-- FALTABA
// Investor editable
const editableInvestor = ref<Investor>({ ...props.investor });

// Watcher para actualizar editableInvestor cuando props.investor cambie
watch(() => props.investor, (newInvestor) => {
  editableInvestor.value = { ...newInvestor };
  // Ocultar los uploaders al cambiar de inversionista
  showFileUploader.value = false;
  showPhotoUploader.value = false;
}, { deep: true });

const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

// Estados para los diálogos
const showConfirmationDialog = ref(false);
const showObservationDialog = ref(false);

// Estados para formularios
const rejectionReason = ref('');
const commentText = ref('');
const comment2Text = ref('');
const observationComment = ref('');
const selectedFile = ref<File | null>(null);

// Estados de control
const loading = ref(false);
const actionType = ref<'approve' | 'reject' | 'approve2' | 'reject2' | ''>('');

// Computed para verificar si está validado
const isValidated = computed(() => {
  return editableInvestor.value.status === 'validated' || editableInvestor.value.status === 'Validado';
});

// Computed para determinar si mostrar segunda validación
const hasFirstValidationComplete = computed(() => {
  return editableInvestor.value.approval1_status === 'approved' ||
    editableInvestor.value.approval1_status === 'rejected' ||
    editableInvestor.value.approval1_status === 'observed';
});

// CORREGIDO: Solo mostrar segunda validación si la primera fue APROBADA
const canShowSecondValidation = computed(() => {
  return editableInvestor.value.approval1_status === 'approved';
});

// Computed para confirmación
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
    case 'approve2':
      return 'pi pi-check-circle text-green-500';
    case 'reject':
    case 'reject2':
      return 'pi pi-exclamation-triangle text-red-500';
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
    case 'approve2':
      return 'success';
    case 'reject':
    case 'reject2':
      return 'danger';
    default: return 'info';
  }
});

// Métodos utilitarios
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
  if (!perfil || perfil === 'Sin perfil' || perfil === null) {
    return 'No';
  }
  return perfil;
};

const formatDate = (date: string | null) => {
  if (!date) return 'No disponible';
  return date; // Ya viene formateado del backend según el JSON
};

const formatSize = (bytes: number) => {
  const k = 1024;
  const dm = 3;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  if (bytes === 0) return '0 B';
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// Métodos para abrir diálogos
const showConfirmDialog = (action: 'approve' | 'reject' | 'approve2' | 'reject2') => {
  actionType.value = action;
  rejectionReason.value = '';
  showConfirmationDialog.value = true;
};

const showObserveDialog = () => {
  observationComment.value = '';
  showObservationDialog.value = true;
};


// Métodos de manejo de archivos
const onFileSelect = (event: any) => {
  selectedFile.value = event.files[0];
  toast.add({
    severity: 'info',
    summary: 'Archivo Seleccionado',
    detail: `${event.files[0].name} está listo para subir`,
    life: 3000
  });
};


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
          approval1_comment: rejectionReason.value
        });
        break;
      case 'approve2':
        response = await axios.put(`/investor/${editableInvestor.value.id}/aprobar-segunda`);
        break;
      case 'reject2':
        response = await axios.put(`/investor/${editableInvestor.value.id}/rechazar-segunda`, {
          approval2_comment: rejectionReason.value
        });
        break;
    }

    if (response) {
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: response.data.message,
        life: 3000
      });

      // Actualizar el investor editable con la nueva data
      editableInvestor.value = { ...response.data.data };
      
      // SOLO cerrar el diálogo de confirmación, NO el principal
      showConfirmationDialog.value = false;
      actionType.value = '';
      rejectionReason.value = '';
      // NO llamar closeDialog() aquí
    }

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al procesar la acción',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const executeObservation = async () => {
  loading.value = true;

  try {
    const response = await axios.put(`/investor/${editableInvestor.value.id}/observaciones`, {
      approval1_comment: observationComment.value
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: response.data.message,
      life: 3000
    });

    // Actualizar el investor editable con la nueva data
    editableInvestor.value = { ...response.data.data };
    
    // SOLO cerrar el diálogo de observación, NO el principal
    showObservationDialog.value = false;
    observationComment.value = '';
    // NO llamar closeDialog() aquí

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al observar inversionista',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

// También actualiza uploadEvidenceFile
const uploadEvidenceFile = async (event: any) => {
  if (!event.files || event.files.length === 0) return;

  const file = event.files[0];
  loading.value = true;

  try {
    const formData = new FormData();
    formData.append('file_path', file);

    const response = await axios.post(`/investor/${editableInvestor.value.id}/adjuntar-primera`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: response.data.message,
      life: 3000
    });

    // Actualizar el investor editable con la nueva data
    editableInvestor.value = { ...response.data.data };
    
    // SOLO ocultar el uploader, NO cerrar el diálogo principal
    showFileUploader.value = false;

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir el archivo',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};


// Mantén closeDialog() solo para el botón "Cerrar" y casos donde realmente quieras cerrar
const closeDialog = () => {
  // Limpiar todos los estados
  showConfirmationDialog.value = false;
  showObservationDialog.value = false;
  showFileUploader.value = false;
  showPhotoUploader.value = false;
  actionType.value = '';
  rejectionReason.value = '';
  observationComment.value = '';
  commentText.value = '';
  comment2Text.value = '';
  loading.value = false;
};

// También actualiza closeConfirmDialog para que NO llame a closeDialog
const closeConfirmDialog = () => {
  showConfirmationDialog.value = false;
  actionType.value = '';
  rejectionReason.value = '';
  // NO llamar closeDialog() aquí
};

// Y closeObserveDialog para que NO llame a closeDialog
const closeObserveDialog = () => {
  showObservationDialog.value = false;
  observationComment.value = '';
  // NO llamar closeDialog() aquí
};

const saveComment = async () => {
  loading.value = true;

  try {
    const response = await axios.put(`/investor/${editableInvestor.value.id}/comentar-primera`, {
      approval1_comment: commentText.value
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: response.data.message,
      life: 3000
    });

    // Actualizar el investor editable con la nueva data
    editableInvestor.value = { ...response.data.data };

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al guardar el comentario',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const saveComment2 = async () => {
  loading.value = true;

  try {
    const response = await axios.put(`/investor/${editableInvestor.value.id}/comentar-segunda`, {
      approval2_comment: comment2Text.value
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: response.data.message,
      life: 3000
    });

    // Actualizar el investor editable con la nueva data
    editableInvestor.value = { ...response.data.data };
    
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al guardar el comentario de segunda validación',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const uploadDocumentFront = async (event: any) => {
  if (!event.files || event.files.length === 0) return;

  const file = event.files[0];
  loading.value = true;

  try {
    const formData = new FormData();
    formData.append('document_front', file);

    const response = await axios.post(`/investor/${editableInvestor.value.id}/upload-document-front`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'DNI frontal actualizado correctamente',
      life: 3000
    });

    // Actualizar el investor editable con la nueva data
    editableInvestor.value = { ...response.data.data };
    showDocumentFrontUploader.value = false;

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir el DNI frontal',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const uploadDocumentBack = async (event: any) => {
  if (!event.files || event.files.length === 0) return;

  const file = event.files[0];
  loading.value = true;

  try {
    const formData = new FormData();
    formData.append('document_back', file);

    const response = await axios.post(`/investor/${editableInvestor.value.id}/upload-document-back`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'DNI posterior actualizado correctamente',
      life: 3000
    });

    editableInvestor.value = { ...response.data.data };
    showDocumentBackUploader.value = false;

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir el DNI posterior',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const uploadInvestorPhoto = async (event: any) => {
  if (!event.files || event.files.length === 0) return;

  const file = event.files[0];
  loading.value = true;

  try {
    const formData = new FormData();
    formData.append('investor_photo_path', file);

    const response = await axios.post(`/investor/${editableInvestor.value.id}/upload-investor-photo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Foto del inversionista actualizada correctamente',
      life: 3000
    });

    editableInvestor.value = { ...response.data.data };
    showInvestorPhotoUploader.value = false;

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Error al subir la foto del inversionista',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

watch(() => props.investor, (newInvestor) => {
  editableInvestor.value = { ...newInvestor };
  // Ocultar TODOS los uploaders al cambiar de inversionista
  showFileUploader.value = false;
  showPhotoUploader.value = false;
  showDocumentFrontUploader.value = false;  // <-- AGREGAR
  showDocumentBackUploader.value = false;   // <-- AGREGAR
  showInvestorPhotoUploader.value = false;  // <-- AGREGAR
}, { deep: true });

const viewDocument = (url: string) => {
  window.open(url, '_blank');
};
</script>
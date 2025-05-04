import { defineStore } from 'pinia'
import axios from 'axios'

interface DocumentUploadState {
  selectedFile: File | null
  fileError: string | null
  remarkError: string | null
  isUploading: boolean
  uploadProgress: number
}

interface UploadResponse {
  message: string
  document: {
    id: number
    doc_id: string
    reference_id: string
    path: string
    extension: string
    url: string
    filename: string
    remark: string
    created_at: string
    updated_at: string
  }
}

export const useDocumentUploadStore = defineStore('documentUpload', {
  state: (): DocumentUploadState => ({
    selectedFile: null,
    fileError: null,
    remarkError: null,
    isUploading: false,
    uploadProgress: 0
  }),

  actions: {
    setFile(file: File | null) {
      this.selectedFile = file
      this.fileError = null
    },

    validateForm(remark: string): boolean {
      let isValid = true
      this.fileError = null
      this.remarkError = null

      if (!this.selectedFile) {
        this.fileError = 'Please select a file'
        isValid = false
      }

      if (!remark.trim()) {
        this.remarkError = 'Please enter a remark'
        isValid = false
      }

      return isValid
    },

    async uploadDocument(referenceId: string, docId: string, remark: string, module: string = 'general'): Promise<UploadResponse | null> {
      if (!this.validateForm(remark)) return null

      try {
        this.isUploading = true
        this.uploadProgress = 0

        const formData = new FormData()
        formData.append('file', this.selectedFile as File)
        formData.append('doc_id', docId)
        formData.append('remark', remark)
        formData.append('module', module)

        const response = await axios.post<UploadResponse>(
          `/api/documents/${referenceId}/upload`,
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
              if (progressEvent.total) {
                this.uploadProgress = Math.round(
                  (progressEvent.loaded * 100) / progressEvent.total
                )
              }
            }
          }
        )

        return response.data
      } catch (error) {
        console.error('Upload failed:', error)
        this.fileError = 'Upload failed. Please try again.'
        return null
      } finally {
        this.isUploading = false
      }
    },

    reset() {
      this.selectedFile = null
      this.fileError = null
      this.remarkError = null
      this.isUploading = false
      this.uploadProgress = 0
    }
  }
})
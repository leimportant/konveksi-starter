import axios from 'axios';
import { defineStore } from 'pinia';

export interface DocumentAttachment {
    id: number;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    reference_id: number;
    reference_type: string;
    created_by: number;
    updated_by: number;
    created_at: string;
    updated_at: string;
}

export const useDocumentAttachmentStore = defineStore('documentAttachment', {
    state: () => ({
        attachments: [] as DocumentAttachment[],
        loading: false,
        error: null as string | null,
    }),

    actions: {
        async uploadAttachment(file: File, referenceId?: string | number, referenceType?: string): Promise<boolean> {
            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();
                formData.append('file', file);
                if (referenceId !== undefined) {
                    formData.append('reference_id', String(referenceId));
                }
                if (referenceType !== undefined) {
                    formData.append('reference_type', referenceType);
                }

                const response = await axios.post('/api/document-attachments/upload', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                if (response.data) {
                    this.attachments.push(response.data);
                } else {
                    throw new Error('Invalid response from server');
                }

                return true;
            } catch (error: unknown) {
                if (axios.isAxiosError(error)) {
                    this.error = error.response?.data?.message || error.message;
                } else if (error instanceof Error) {
                    this.error = error.message;
                } else {
                    this.error = 'Failed to upload attachment';
                }
                return false;
            } finally {
                this.loading = false;
            }
        },

        async fetchAttachments(referenceId: string | number, referenceType: string): Promise<void> {
            console.log('Fetching attachments for:', referenceId, referenceType);
            if (!referenceId || !referenceType) {
                this.error = 'Reference ID and type are required';
                return;
            }
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(`/api/document-attachments`, {
                    params: {
                        reference_id: String(referenceId),
                        reference_type: referenceType,
                    },
                });
                this.attachments = response.data || [];
            } catch (error: unknown) {
                if (axios.isAxiosError(error)) {
                    this.error = error.response?.data?.message || error.message;
                } else if (error instanceof Error) {
                    this.error = error.message;
                } else {
                    this.error = 'Failed to fetch attachments';
                }
            } finally {
                this.loading = false;
            }
        },

        async deleteAttachment(id: number): Promise<boolean> {
            this.error = null;
            try {
                await axios.delete(`/api/document-attachments/${id}`);
                this.attachments = this.attachments.filter((doc) => doc.id !== id);
                return true;
            } catch (error: unknown) {
                if (axios.isAxiosError(error)) {
                    this.error = error.response?.data?.message || error.message;
                } else if (error instanceof Error) {
                    this.error = error.message;
                } else {
                    this.error = 'Failed to delete attachment';
                }
                return false;
            }
        },

        resetAttachments(): void {
            this.attachments = [];
            this.error = null;
            this.loading = false;
        },
    },
});

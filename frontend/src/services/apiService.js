import axios from 'axios';
import { API_ENDPOINTS, API_CONFIG } from '../config/api';

// Create axios instance with default config
const apiClient = axios.create({
    baseURL: API_ENDPOINTS.INVESTORS.replace('/api/investors.php', ''),
    headers: API_CONFIG.headers,
});

// Request interceptor
apiClient.interceptors.request.use(
    (config) => {
        // Add any request modifications here
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
apiClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        console.error('API Error:', error);
        return Promise.reject(error);
    }
);

// API Service functions
export const apiService = {
    // Create new investor
    createInvestor: async (investorData) => {
        try {
            const response = await apiClient.post(API_ENDPOINTS.INVESTORS, investorData);
            return response.data;
        } catch (error) {
            throw error.response?.data || { status: 'error', message: 'Network error' };
        }
    },

    // Get all investors
    getInvestors: async () => {
        try {
            const response = await apiClient.get(API_ENDPOINTS.INVESTORS);
            return response.data;
        } catch (error) {
            throw error.response?.data || { status: 'error', message: 'Network error' };
        }
    },

    // Upload documents (multiple files)
    uploadDocuments: async (investorId, files) => {
        try {
            const formData = new FormData();
            formData.append('investor_id', investorId);

            // Append each file to the FormData
            if (Array.isArray(files)) {
                files.forEach((file, index) => {
                    formData.append('documents', file);
                });
            } else {
                // Handle single file case
                formData.append('documents', files);
            }

            const response = await apiClient.post(API_ENDPOINTS.UPLOAD, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data;
        } catch (error) {
            throw error.response?.data || { status: 'error', message: 'Network error' };
        }
    },

    // Upload document (single file - for backward compatibility)
    uploadDocument: async (investorId, file) => {
        try {
            const formData = new FormData();
            formData.append('investor_id', investorId);
            formData.append('documents', file);

            const response = await apiClient.post(API_ENDPOINTS.UPLOAD, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data;
        } catch (error) {
            throw error.response?.data || { status: 'error', message: 'Network error' };
        }
    },

    // Get documents for an investor
    getDocuments: async (investorId) => {
        try {
            const response = await apiClient.get(`${API_ENDPOINTS.UPLOAD}?investor_id=${investorId}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || { status: 'error', message: 'Network error' };
        }
    },
};

export default apiService; 
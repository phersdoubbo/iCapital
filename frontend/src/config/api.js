// API Configuration for iCapital
export const API_BASE_URL = 'https://confluence.vazquezulloa.com';

export const API_ENDPOINTS = {
    INVESTORS: `${API_BASE_URL}/api/icapital-investors.php`,
    UPLOAD: `${API_BASE_URL}/api/icapital-upload.php`,
};

export const API_CONFIG = {
    headers: {
        'Content-Type': 'application/json',
    },
}; 
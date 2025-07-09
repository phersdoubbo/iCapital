import React, { useState, useEffect } from 'react';
import apiService from '../services/apiService';
import './InvestorList.css';

const InvestorList = ({ refreshTrigger }) => {
    const [investors, setInvestors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [investorDocuments, setInvestorDocuments] = useState({});

    const fetchInvestors = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await apiService.getInvestors();
            if (response.status === 'success') {
                setInvestors(response.data);
                // Fetch documents for each investor
                await fetchDocumentsForInvestors(response.data);
            } else {
                setError('Failed to fetch investors');
            }
        } catch (err) {
            setError(err.message || 'Failed to fetch investors');
        } finally {
            setLoading(false);
        }
    };

    const fetchDocumentsForInvestors = async (investorsList) => {
        const documentsMap = {};

        for (const investor of investorsList) {
            try {
                const response = await apiService.getDocuments(investor.id);
                if (response.status === 'success') {
                    documentsMap[investor.id] = response.data;
                } else {
                    documentsMap[investor.id] = [];
                }
            } catch (err) {
                console.error(`Failed to fetch documents for investor ${investor.id}:`, err);
                documentsMap[investor.id] = [];
            }
        }

        setInvestorDocuments(documentsMap);
    };

    useEffect(() => {
        fetchInvestors();
    }, [refreshTrigger]);

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatPhone = (phone) => {
        // Simple phone formatting
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
        }
        return phone;
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const getDownloadUrl = (investorId, storedFilename) => {
        return `https://confluence.vazquezulloa.com/uploads/investors/${investorId}/${storedFilename}`;
    };

    if (loading) {
        return (
            <div className="investor-list-container">
                <h2>Investor List</h2>
                <div className="loading">Loading investors...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="investor-list-container">
                <h2>Investor List</h2>
                <div className="error-message">{error}</div>
                <button onClick={fetchInvestors} className="retry-button">
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="investor-list-container">
            <h2>Investor List ({investors.length})</h2>

            {investors.length === 0 ? (
                <div className="no-investors">
                    <p>No investors found. Add your first investor using the form above.</p>
                </div>
            ) : (
                <div className="investors-grid">
                    {investors.map((investor) => (
                        <div key={investor.id} className="investor-card">
                            <div className="investor-header">
                                <h3>{investor.first_name} {investor.last_name}</h3>
                                <span className="investor-id">ID: {investor.id}</span>
                            </div>

                            <div className="investor-details">
                                <div className="detail-row">
                                    <span className="label">Date of Birth:</span>
                                    <span className="value">{formatDate(investor.date_of_birth)}</span>
                                </div>

                                <div className="detail-row">
                                    <span className="label">Phone:</span>
                                    <span className="value">{formatPhone(investor.phone_number)}</span>
                                </div>

                                <div className="detail-row">
                                    <span className="label">Address:</span>
                                    <span className="value">
                                        {investor.street_address}<br />
                                        {investor.state} {investor.zip_code}
                                    </span>
                                </div>

                                <div className="detail-row">
                                    <span className="label">Added:</span>
                                    <span className="value">{formatDate(investor.created_at)}</span>
                                </div>

                                {/* Documents Section */}
                                <div className="documents-section">
                                    <span className="label">Documents:</span>
                                    <div className="documents-list">
                                        {investorDocuments[investor.id] && investorDocuments[investor.id].length > 0 ? (
                                            investorDocuments[investor.id].map((document) => (
                                                <div key={document.id} className="document-item">
                                                    <a
                                                        href={getDownloadUrl(investor.id, document.stored_filename)}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="document-link"
                                                    >
                                                        📄 {document.original_filename}
                                                    </a>
                                                    <span className="document-info">
                                                        ({formatFileSize(document.file_size)} • {formatDate(document.upload_date)})
                                                    </span>
                                                </div>
                                            ))
                                        ) : (
                                            <span className="no-documents">No documents uploaded</span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default InvestorList; 
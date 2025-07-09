import React, { useState } from 'react';
import InvestorForm from './components/InvestorForm';
import InvestorList from './components/InvestorList';
import apiService from './services/apiService';
import './App.css';

function App() {
    const [refreshTrigger, setRefreshTrigger] = useState(0);
    const [successMessage, setSuccessMessage] = useState('');

    const handleSubmit = async (investorData, files) => {
        try {
            // First, create the investor
            const investorResponse = await apiService.createInvestor(investorData);

            if (investorResponse.status === 'success') {
                // Then upload the documents
                const uploadResponse = await apiService.uploadDocuments(
                    investorResponse.investor_id,
                    files
                );

                if (uploadResponse.status === 'success' || uploadResponse.status === 'partial') {
                    return {
                        status: 'success',
                        message: uploadResponse.status === 'success'
                            ? 'Investor and documents added successfully!'
                            : 'Investor created and some documents uploaded successfully!',
                        investor: investorResponse.data,
                        documents: uploadResponse.data
                    };
                } else {
                    throw new Error('Failed to upload documents');
                }
            } else {
                throw new Error('Failed to create investor');
            }
        } catch (error) {
            console.error('Submission error:', error);
            throw error;
        }
    };

    const handleSuccess = (result) => {
        setSuccessMessage(result.message);
        setRefreshTrigger(prev => prev + 1);

        // Clear success message after 5 seconds
        setTimeout(() => {
            setSuccessMessage('');
        }, 5000);
    };

    return (
        <div className="App">
            <header className="App-header">
                <h1>iCapital Investor Management</h1>
                <p>Partner Portal for Investor Information and Document Upload</p>
            </header>

            <main className="App-main">
                {successMessage && (
                    <div className="success-message">
                        {successMessage}
                    </div>
                )}

                <InvestorForm
                    onSubmit={handleSubmit}
                    onSuccess={handleSuccess}
                />

                <InvestorList refreshTrigger={refreshTrigger} />
            </main>

            <footer className="App-footer">
                <p>&copy; 2024 iCapital. All rights reserved.</p>
            </footer>
        </div>
    );
}

export default App; 
import React, { useState } from 'react';
import './InvestorForm.css';

const InvestorForm = ({ onSubmit, onSuccess }) => {
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        date_of_birth: '',
        phone_number: '',
        street_address: '',
        state: '',
        zip_code: ''
    });

    const [selectedFile, setSelectedFile] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState({});

    // US States for dropdown
    const states = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
        'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
        'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
        'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
        'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
    ];

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));

        // Clear error when user starts typing
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: ''
            }));
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (3MB limit)
            if (file.size > 3 * 1024 * 1024) {
                setErrors(prev => ({
                    ...prev,
                    file: 'File size must be less than 3MB'
                }));
                setSelectedFile(null);
                return;
            }

            // Validate file type
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain'
            ];

            if (!allowedTypes.includes(file.type)) {
                setErrors(prev => ({
                    ...prev,
                    file: 'File type not allowed. Allowed types: PDF, DOC, DOCX, JPG, PNG, GIF, TXT'
                }));
                setSelectedFile(null);
                return;
            }

            setSelectedFile(file);
            setErrors(prev => ({
                ...prev,
                file: ''
            }));
        }
    };

    const validateForm = () => {
        const newErrors = {};

        // Required field validation
        Object.keys(formData).forEach(key => {
            if (!formData[key].trim()) {
                newErrors[key] = `${key.replace('_', ' ')} is required`;
            }
        });

        // Date validation
        if (formData.date_of_birth) {
            const date = new Date(formData.date_of_birth);
            const today = new Date();
            if (date > today) {
                newErrors.date_of_birth = 'Date of birth cannot be in the future';
            }
        }

        // Phone number validation
        if (formData.phone_number && !/^[\d\-\+\(\)\s]+$/.test(formData.phone_number)) {
            newErrors.phone_number = 'Invalid phone number format';
        }

        // Zip code validation
        if (formData.zip_code && !/^\d{5}(-\d{4})?$/.test(formData.zip_code)) {
            newErrors.zip_code = 'Invalid zip code format (e.g., 12345 or 12345-6789)';
        }

        // File validation
        if (!selectedFile) {
            newErrors.file = 'Please select a file to upload';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setIsSubmitting(true);

        try {
            const result = await onSubmit(formData, selectedFile);

            if (result.status === 'success') {
                // Reset form
                setFormData({
                    first_name: '',
                    last_name: '',
                    date_of_birth: '',
                    phone_number: '',
                    street_address: '',
                    state: '',
                    zip_code: ''
                });
                setSelectedFile(null);

                if (onSuccess) {
                    onSuccess(result);
                }
            }
        } catch (error) {
            console.error('Submission error:', error);
            setErrors({
                submit: error.message || 'Failed to submit investor information'
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="investor-form-container">
            <h2>Add New Investor</h2>
            <form onSubmit={handleSubmit} className="investor-form">
                <div className="form-row">
                    <div className="form-group">
                        <label htmlFor="first_name">First Name *</label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value={formData.first_name}
                            onChange={handleInputChange}
                            className={errors.first_name ? 'error' : ''}
                        />
                        {errors.first_name && <span className="error-message">{errors.first_name}</span>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="last_name">Last Name *</label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value={formData.last_name}
                            onChange={handleInputChange}
                            className={errors.last_name ? 'error' : ''}
                        />
                        {errors.last_name && <span className="error-message">{errors.last_name}</span>}
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group">
                        <label htmlFor="date_of_birth">Date of Birth *</label>
                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value={formData.date_of_birth}
                            onChange={handleInputChange}
                            className={errors.date_of_birth ? 'error' : ''}
                        />
                        {errors.date_of_birth && <span className="error-message">{errors.date_of_birth}</span>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="phone_number">Phone Number *</label>
                        <input
                            type="tel"
                            id="phone_number"
                            name="phone_number"
                            value={formData.phone_number}
                            onChange={handleInputChange}
                            placeholder="555-123-4567"
                            className={errors.phone_number ? 'error' : ''}
                        />
                        {errors.phone_number && <span className="error-message">{errors.phone_number}</span>}
                    </div>
                </div>

                <div className="form-group">
                    <label htmlFor="street_address">Street Address *</label>
                    <input
                        type="text"
                        id="street_address"
                        name="street_address"
                        value={formData.street_address}
                        onChange={handleInputChange}
                        placeholder="123 Main Street"
                        className={errors.street_address ? 'error' : ''}
                    />
                    {errors.street_address && <span className="error-message">{errors.street_address}</span>}
                </div>

                <div className="form-row">
                    <div className="form-group">
                        <label htmlFor="state">State *</label>
                        <select
                            id="state"
                            name="state"
                            value={formData.state}
                            onChange={handleInputChange}
                            className={errors.state ? 'error' : ''}
                        >
                            <option value="">Select State</option>
                            {states.map(state => (
                                <option key={state} value={state}>{state}</option>
                            ))}
                        </select>
                        {errors.state && <span className="error-message">{errors.state}</span>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="zip_code">Zip Code *</label>
                        <input
                            type="text"
                            id="zip_code"
                            name="zip_code"
                            value={formData.zip_code}
                            onChange={handleInputChange}
                            placeholder="12345"
                            className={errors.zip_code ? 'error' : ''}
                        />
                        {errors.zip_code && <span className="error-message">{errors.zip_code}</span>}
                    </div>
                </div>

                <div className="form-group">
                    <label htmlFor="document">Upload Document *</label>
                    <input
                        type="file"
                        id="document"
                        name="document"
                        onChange={handleFileChange}
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt"
                        className={errors.file ? 'error' : ''}
                    />
                    <small>Maximum file size: 3MB. Allowed types: PDF, DOC, DOCX, JPG, PNG, GIF, TXT</small>
                    {errors.file && <span className="error-message">{errors.file}</span>}
                    {selectedFile && (
                        <div className="file-info">
                            <strong>Selected file:</strong> {selectedFile.name} ({(selectedFile.size / 1024 / 1024).toFixed(2)} MB)
                        </div>
                    )}
                </div>

                {errors.submit && (
                    <div className="error-message submit-error">{errors.submit}</div>
                )}

                <button
                    type="submit"
                    className="submit-button"
                    disabled={isSubmitting}
                >
                    {isSubmitting ? 'Submitting...' : 'Add Investor'}
                </button>
            </form>
        </div>
    );
};

export default InvestorForm; 
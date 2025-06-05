/**
 * Integration examples showing how to extend Spider Boxes with hooks
 * 
 * These examples demonstrate how third-party developers can extend
 * Spider Boxes functionality without modifying core files.
 */

import { SpiderBoxesHooks, SPIDER_BOXES_HOOKS } from '../hooks/useSpiderBoxesHooks';
import React from 'react';

/**
 * Example 1: Adding custom validation to forms
 */
export const setupCustomValidation = () => {
  // Add email validation
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.FORM_VALIDATION,
    'custom-plugin/email-validation',
    (errors: Record<string, string>, formData: Record<string, any>) => {
      if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
        errors.email = 'Please enter a valid email address';
      }
      return errors;
    }
  );

  // Add phone number validation
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.FORM_VALIDATION,
    'custom-plugin/phone-validation',
    (errors: Record<string, string>, formData: Record<string, any>) => {
      if (formData.phone && !/^\+?[\d\s\-\(\)]+$/.test(formData.phone)) {
        errors.phone = 'Please enter a valid phone number';
      }
      return errors;
    }
  );
};

/**
 * Example 2: Adding custom content before/after components
 */
export const setupCustomContent = () => {
  // Add disclaimer before forms
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.BEFORE_CONTENT,
    'custom-plugin/form-disclaimer',
    (content: React.ReactNode) => (
      <>
        {content}
        <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
          <div className="flex">
            <div className="ml-3">
              <p className="text-sm text-yellow-700">
                <strong>Privacy Notice:</strong> Your information will be handled according to our privacy policy.
              </p>
            </div>
          </div>
        </div>
      </>
    )
  );

  // Add powered by notice after content
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.AFTER_CONTENT,
    'custom-plugin/powered-by',
    (content: React.ReactNode) => (
      <>
        {content}
        <div className="text-center text-xs text-gray-500 mt-4">
          Powered by Spider Boxes & Custom Extensions
        </div>
      </>
    )
  );
};

/**
 * Example 3: Field value transformations
 */
export const setupFieldTransformations = () => {
  // Automatically capitalize names
  SpiderBoxesHooks.addFilter(
    'spider_boxes_field_value_text',
    'custom-plugin/capitalize-names',
    (value: string, field: any) => {
      if (field.id === 'name' || field.id === 'first_name' || field.id === 'last_name') {
        return value
          .split(' ')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
          .join(' ');
      }
      return value;
    }
  );

  // Format phone numbers
  SpiderBoxesHooks.addFilter(
    'spider_boxes_field_value_text',
    'custom-plugin/format-phone',
    (value: string, field: any) => {
      if (field.id === 'phone' && value) {
        // Remove all non-digit characters
        const digits = value.replace(/\D/g, '');
        
        // Format as (XXX) XXX-XXXX for US numbers
        if (digits.length === 10) {
          return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
        }
      }
      return value;
    }
  );
};

/**
 * Example 4: Custom analytics tracking
 */
export const setupAnalytics = () => {
  // Track component mounts
  SpiderBoxesHooks.addAction(
    SPIDER_BOXES_HOOKS.COMPONENT_MOUNT,
    'analytics/track-component-mount',
    (componentName: string) => {
      // Custom analytics tracking
      if (typeof gtag !== 'undefined') {
        gtag('event', 'component_mount', {
          component_name: componentName,
          plugin: 'spider-boxes'
        });
      }
    }
  );

  // Track form submissions
  SpiderBoxesHooks.addAction(
    SPIDER_BOXES_HOOKS.AFTER_FORM_SUBMIT,
    'analytics/track-form-submit',
    (formData: Record<string, any>) => {
      if (typeof gtag !== 'undefined') {
        gtag('event', 'form_submit', {
          form_fields: Object.keys(formData).join(','),
          plugin: 'spider-boxes'
        });
      }
    }
  );

  // Track field interactions
  SpiderBoxesHooks.addAction(
    SPIDER_BOXES_HOOKS.FIELD_VALUE_CHANGED,
    'analytics/track-field-interaction',
    ({ fieldId }: { fieldId: string }) => {
      if (typeof gtag !== 'undefined') {
        gtag('event', 'field_interaction', {
          field_id: fieldId,
          plugin: 'spider-boxes'
        });
      }
    }
  );
};

/**
 * Example 5: Dynamic field modifications
 */
export const setupDynamicFields = () => {
  // Show/hide fields based on other field values
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.BEFORE_FIELD_RENDER,
    'custom-plugin/conditional-fields',
    (content: React.ReactNode, field: any) => {
      // Example: Hide "company" field if user type is "individual"
      if (field.id === 'company') {
        const userType = document.querySelector('[name="user_type"]')?.value;
        if (userType === 'individual') {
          return <div style={{ display: 'none' }}>{content}</div>;
        }
      }
      return content;
    }
  );

  // Add required indicators to fields
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.BEFORE_FIELD_RENDER,
    'custom-plugin/required-indicator',
    (content: React.ReactNode, field: any) => {
      if (field.validation?.required) {
        return (
          <div className="relative">
            {content}
            <span className="absolute top-0 right-0 text-red-500 text-sm">*</span>
          </div>
        );
      }
      return content;
    }
  );
};

/**
 * Example 6: Integration with third-party services
 */
export const setupThirdPartyIntegration = () => {
  // Send data to CRM on form submission
  SpiderBoxesHooks.addAction(
    SPIDER_BOXES_HOOKS.AFTER_FORM_SUBMIT,
    'crm-integration/send-to-crm',
    async (formData: Record<string, any>) => {
      try {
        await fetch('/wp-json/my-plugin/v1/crm-sync', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': (window as any).wpApiSettings?.nonce
          },
          body: JSON.stringify(formData)
        });
      } catch (error) {
        console.error('CRM sync failed:', error);
      }
    }
  );

  // Add reCAPTCHA to forms
  SpiderBoxesHooks.addFilter(
    SPIDER_BOXES_HOOKS.AFTER_CONTENT,
    'recaptcha/add-recaptcha',
    (content: React.ReactNode) => (
      <>
        {content}
        <div className="recaptcha-container mt-4">
          {/* reCAPTCHA component would go here */}
          <div className="text-sm text-gray-600">
            This site is protected by reCAPTCHA
          </div>
        </div>
      </>
    )
  );
};

/**
 * Main setup function to initialize all extensions
 * Call this from your plugin's main file
 */
export const initializeCustomExtensions = () => {
  setupCustomValidation();
  setupCustomContent();
  setupFieldTransformations();
  setupAnalytics();
  setupDynamicFields();
  setupThirdPartyIntegration();

  console.log('Spider Boxes custom extensions initialized');
};

// Auto-initialize if this file is loaded
if (typeof window !== 'undefined') {
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCustomExtensions);
  } else {
    initializeCustomExtensions();
  }
}

// Export for manual initialization
export default initializeCustomExtensions;

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

function normalizeForDirty(value) {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'string') {
        return value
            .replace(/\r\n/g, '\n')
            .trim();
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return JSON.stringify(value);
}

function getValueByPath(source, path) {
    return path
        .split('.')
        .reduce((value, key) => value?.[key], source);
}

/**
 * Check if a field in form is dirty
 * Can be used for highlight unsaved field for example
 * 
 * @param form - The vue form related to field
 * @param initialValues - The ref of initial values
 * @param fieldPath - The field to check if is dirty
 * @returns {boolean}
 */
function isFieldDirty(form, initialValues, fieldPath) {
    return normalizeForDirty(getValueByPath(form, fieldPath))
        !== normalizeForDirty(getValueByPath(initialValues, fieldPath));
}

/**
 * 
 * @param form - The vue form
 * @param initialValues - The ref to inital values
 * @param fields - Array of fields to check
 * @returns {*}
 */
function hasDirtyFields(form, initialValues, fields) {
    return fields.some((fieldPath) => {
        return isFieldDirty(form, initialValues, fieldPath);
    });
}

export { clone, normalizeForDirty, isFieldDirty, hasDirtyFields}

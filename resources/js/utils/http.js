export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function csrfHeaders() {
    return {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    }
}

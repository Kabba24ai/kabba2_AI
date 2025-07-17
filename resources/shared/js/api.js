window.apiFetch = async function(input, init = {}) {
    let response;
    let resData;

    try {
        response = await fetch(input, init);
        resData = await response.json();
    } catch (error) {
        notyf.error('A network error occurred. Please try again.');
        throw error;
    }

    if (response.ok && resData.success) {
        return resData;
    } else if (response.status === 422 && resData.errors) {
        // Validation errors from Laravel
        Object.values(resData.errors).flat().forEach(errorMsg => notyf.error(errorMsg));
        throw { type: 'validation', response, resData }; // Optionally throw to halt execution
    } else {
        notyf.error(resData.message || 'An error occurred. Please try again.');
        throw { type: 'api', response, resData }; // Optionally throw to halt execution
    }
};

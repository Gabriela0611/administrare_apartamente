(function () {
    function findOption(select, value) {
        if (!select || value === undefined || value === null || value === '') {
            return null;
        }

        return Array.from(select.options).find((option) => option.value === String(value)) || null;
    }

    function setSelectValue(select, value) {
        const option = findOption(select, value);

        if (!option) {
            return false;
        }

        select.value = option.value;
        return true;
    }

    function setEmptySelect(select) {
        if (!select) {
            return;
        }

        if (findOption(select, '0')) {
            select.value = '0';
            return;
        }

        if (findOption(select, '')) {
            select.value = '';
        }
    }

    function selectedText(select) {
        if (!select || select.selectedIndex < 0) {
            return '';
        }

        return select.options[select.selectedIndex].textContent.trim();
    }

    function setupForm(form) {
        const apartmentSelect = form.querySelector('select[name="apartament_id"]');
        const tenantSelect = form.querySelector('select[name="chirias_id"]');
        const tenantHidden = form.querySelector('input[type="hidden"][name="chirias_id"]');
        const tenantDisplay = form.querySelector('#chirias_afisat, [data-sync-display="chirias"]');

        if (!apartmentSelect || (!tenantSelect && !tenantHidden)) {
            return;
        }

        apartmentSelect.addEventListener('change', function () {
            const option = apartmentSelect.options[apartmentSelect.selectedIndex];
            const tenantId = option ? option.dataset.chiriasId || '' : '';
            const tenantName = option ? option.dataset.chiriasName || '' : '';

            if (tenantSelect) {
                if (tenantId && !setSelectValue(tenantSelect, tenantId)) {
                    setEmptySelect(tenantSelect);
                } else if (!tenantId) {
                    setEmptySelect(tenantSelect);
                }
            }

            if (tenantHidden) {
                tenantHidden.value = tenantId;
            }

            if (tenantDisplay) {
                tenantDisplay.value = tenantName || 'Niciun chirias asociat';
            }
        });

        if (tenantSelect) {
            tenantSelect.addEventListener('change', function () {
                const option = tenantSelect.options[tenantSelect.selectedIndex];
                const apartmentId = option ? option.dataset.apartamentId || '' : '';

                if (apartmentId) {
                    setSelectValue(apartmentSelect, apartmentId);
                }

                if (tenantDisplay) {
                    tenantDisplay.value = selectedText(tenantSelect);
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(setupForm);
    });
}());

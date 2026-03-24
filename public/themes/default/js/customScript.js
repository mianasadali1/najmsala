/* Top bar scrolling start */
document.addEventListener('DOMContentLoaded', () => {
    const dbMain = document.querySelector('.db-main');
    const subHeader = document.querySelector('.sub-header');
    const dbHeader = document.querySelector('.db-header');

    if (dbMain) {
        let headerSize = dbMain.offsetWidth;
        if (headerSize < 736) {
            if (subHeader) {
                subHeader.style.top = `${dbHeader.scrollHeight}px`;
            }

            dbMain.addEventListener('scroll', () => {
                let scroll = dbMain.scrollTop;
                if (scroll === 0) {
                    subHeader.style.display = 'block';
                } else {
                    subHeader.style.display = 'none';
                }
            });
        }
    }
});


/* Variation start */

document.addEventListener('click', function (event) {
    if (event.target.matches('.size-tabs label')) {
        document.querySelectorAll('.size-tabs label').forEach((label) => {
            label.classList.remove('active');
        });
        event.target.classList.add('active');
    }
});

document.addEventListener('click', function (event) {
    if (event.target.closest('.extra-swiper .extra')) {
        const extra = event.target.closest('.extra-swiper .extra');
        const input = extra.querySelector('input');
        if (input) {
            input.checked = !input.checked;
            extra.classList.toggle('active', input.checked);
        }
    }
});

document.addEventListener('click', function (event) {
    if (event.target.closest('.addon-swiper .addon')) {
        const addon = event.target.closest('.addon-swiper .addon');
        addon.classList.toggle('active');
    }
});
/* Variation end */

/* Other button tab stat */
document.querySelectorAll('.other-tabBtn').forEach(function (tabBtn) {
    tabBtn.addEventListener('click', function () {
        document.querySelectorAll('.other-tabBtn').forEach(function (btn) {
            btn.classList.remove('active');
            const tab = btn.getAttribute('data-tab');
            if (tab) {
                const tabElement = document.querySelector(tab);
                if (tabElement) {
                    tabElement.classList.remove('active');
                }
            }
        });

        this.classList.add('active');
        const dataTab = this.getAttribute('data-tab');
        if (dataTab) {
            const tabElement = document.querySelector(dataTab);
            if (tabElement) {
                tabElement.classList.add('active');
            }
        }
    });
});
/* Other button tab end */

document.addEventListener('click', function (event) {
    if (event.target.classList.contains('db-tab-btn')) {
        document.querySelectorAll('.db-tab-btn').forEach((btn) => {
            btn.classList.remove('active');
            const tabSelector = btn.getAttribute('data-tab');
            if (tabSelector) {
                const tab = document.querySelector(tabSelector);
                if (tab) {
                    tab.classList.remove('active');
                }
            }
        });
        event.target.classList.add('active');
        const dataTab = event.target.getAttribute('data-tab');
        if (dataTab) {
            const tab = document.querySelector(dataTab);
            if (tab) {
                tab.classList.add('active');
            }
        }
    }
});
/* db tab button end */

/* POS responsive start */

document.querySelectorAll('.db-pos-cartBtn').forEach(function (cartBtn) {
    cartBtn.addEventListener('click', function () {
        const cartDiv = document.querySelector('.db-pos-cartDiv');
        if (!cartDiv.classList.contains('active')) {
            cartDiv.classList.add('active');
        } else {
            cartDiv.classList.remove('active');
        }
    });
});

document.querySelectorAll('.db-pos-cartCls').forEach(function (cartCls) {
    cartCls.addEventListener('click', function () {
        document.querySelector('.db-pos-cartDiv').classList.remove('active');
    });
});

/* POS responsive close */


/* Message code start */
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('close-the-image-file')) {
        event.target.parentElement.remove();
        document.querySelector('.chat-footer-data-list')?.classList.add('hidden');
    }
});

/* Message code end */


/* Installer menu active code start */
const handleLinkForInstaller = (param) => {
    window.location.replace(param);
};

document.querySelectorAll('.close-alert-button').forEach(function (button) {
    button.addEventListener('click', function () {
        this.parentElement.remove();
    });
});

/* Installer menu active code close */


/* Setting menu code start */

document.addEventListener('click', function (e) {
    const dropdownButton = e.target.closest('.dropdown-btn');

    if (dropdownButton) {
        e.stopPropagation();

        const dropdownGroup = dropdownButton.parentElement;
        const dropdownList = dropdownGroup.querySelector('.dropdown-list');

        if (!dropdownList.classList.contains('active')) {
            const allDropdownLists = document.querySelectorAll('.dropdown-list');
            allDropdownLists.forEach(list => list.classList.remove('active'));
        }

        dropdownList.classList.toggle('active');
    } else {
        document.querySelectorAll('.dropdown-list').forEach(function (list) {
            list.classList.remove('active');
        });
    }
});

/* Setting menu code end */





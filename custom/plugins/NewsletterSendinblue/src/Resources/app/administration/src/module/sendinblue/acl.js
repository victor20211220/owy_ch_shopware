Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'additional_permissions',
        parent: null,
        key: 'system',
        roles: {
            sendinblue: {
                privileges: [],
                dependencies: [
                ],
            },
        },
    });

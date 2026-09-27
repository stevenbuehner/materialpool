const legacyDefaultPermissions = [
	'materials.create',
	'materials.update-own',
	'materials.update-metadata-own',
	'materials.delete-own',
	'resources.create',
	'resources.update-own',
	'resources.delete-own',
];

export function userHasPermission(user, permission) {
	if (user?.is_admin === true) return true;
	const permissions = Array.isArray(user?.permissions) ? user.permissions : legacyDefaultPermissions;

	return permissions.includes(permission);
}

export function userCanManageOwnOrAll(user, record, ownPermission, allPermission) {
	return userHasPermission(user, allPermission)
		|| (record?.created_by === user?.id && userHasPermission(user, ownPermission));
}

export function userCanManageMaterialUsage(user, usage, material) {
	return user?.is_admin === true
		|| usage?.created_by === user?.id
		|| material?.created_by === user?.id;
}

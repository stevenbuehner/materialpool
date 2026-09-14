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

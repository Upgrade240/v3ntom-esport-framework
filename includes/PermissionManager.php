<?php

class PermissionManager {
    private $db;
    private $userPermissions = [];
    private $roleHierarchy = [];
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadRoleHierarchy();
    }
    
    private function loadRoleHierarchy() {
        $result = $this->db->select('roles', 'id, name, hierarchy_level, permissions, assignable_roles', '', [], 'hierarchy_level DESC');
        $roles = $this->db->fetchAll($result);
        
        foreach ($roles as $role) {
            $this->roleHierarchy[$role['id']] = [
                'name' => $role['name'],
                'level' => $role['hierarchy_level'],
                'permissions' => json_decode($role['permissions'], true) ?: [],
                'assignable_roles' => json_decode($role['assignable_roles'], true) ?: []
            ];
        }
    }
    
    public function getUserPermissions($userId) {
        if (isset($this->userPermissions[$userId])) {
            return $this->userPermissions[$userId];
        }
        
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return [];
        }
        
        $permissions = [];
        
        // Get primary role permissions
        if (isset($this->roleHierarchy[$user['role_id']])) {
            $permissions = array_merge($permissions, $this->roleHierarchy[$user['role_id']]['permissions']);
        }
        
        // Get additional role permissions
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        foreach ($additionalRoles as $roleId) {
            if (isset($this->roleHierarchy[$roleId])) {
                $permissions = array_merge($permissions, $this->roleHierarchy[$roleId]['permissions']);
            }
        }
        
        $this->userPermissions[$userId] = $permissions;
        return $permissions;
    }
    
    public function hasPermission($userId, $permission) {
        $permissions = $this->getUserPermissions($userId);
        
        // Check for wildcard permissions
        if (isset($permissions['*']) && $permissions['*'] === true) {
            return true;
        }
        
        // Check exact permission
        if (isset($permissions[$permission]) && $permissions[$permission] === true) {
            return true;
        }
        
        // Check wildcard patterns
        foreach ($permissions as $perm => $value) {
            if ($value === true && $this->matchesWildcard($permission, $perm)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function matchesWildcard($permission, $pattern) {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
        return preg_match($regex, $permission) === 1;
    }
    
    public function canAssignRole($userId, $targetRoleId) {
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        // Super admin can assign any role
        if ($this->hasPermission($userId, '*')) {
            return true;
        }
        
        // Check if user's primary role can assign this role
        $userRole = $this->roleHierarchy[$user['role_id']] ?? null;
        if ($userRole && in_array($targetRoleId, $userRole['assignable_roles'])) {
            return true;
        }
        
        // Check additional roles
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        foreach ($additionalRoles as $roleId) {
            $role = $this->roleHierarchy[$roleId] ?? null;
            if ($role && in_array($targetRoleId, $role['assignable_roles'])) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getAssignableRoles($userId) {
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return [];
        }
        
        $assignableRoleIds = [];
        
        // Get assignable roles from primary role
        $userRole = $this->roleHierarchy[$user['role_id']] ?? null;
        if ($userRole) {
            $assignableRoleIds = array_merge($assignableRoleIds, $userRole['assignable_roles']);
        }
        
        // Get assignable roles from additional roles
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        foreach ($additionalRoles as $roleId) {
            $role = $this->roleHierarchy[$roleId] ?? null;
            if ($role) {
                $assignableRoleIds = array_merge($assignableRoleIds, $role['assignable_roles']);
            }
        }
        
        // Remove duplicates and get role details
        $assignableRoleIds = array_unique($assignableRoleIds);
        $roles = [];
        
        foreach ($assignableRoleIds as $roleId) {
            if (isset($this->roleHierarchy[$roleId])) {
                $result = $this->db->getRoleById($roleId);
                if ($result) {
                    $roles[] = $result;
                }
            }
        }
        
        return $roles;
    }
    
    public function getUserHierarchyLevel($userId) {
        $user = $this->db->getUserById($userId);
        if (!$user) {
            return 0;
        }
        
        $maxLevel = 0;
        
        // Check primary role
        if (isset($this->roleHierarchy[$user['role_id']])) {
            $maxLevel = max($maxLevel, $this->roleHierarchy[$user['role_id']]['level']);
        }
        
        // Check additional roles
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        foreach ($additionalRoles as $roleId) {
            if (isset($this->roleHierarchy[$roleId])) {
                $maxLevel = max($maxLevel, $this->roleHierarchy[$roleId]['level']);
            }
        }
        
        return $maxLevel;
    }
    
    public function canModerateUser($moderatorId, $targetUserId) {
        $moderatorLevel = $this->getUserHierarchyLevel($moderatorId);
        $targetLevel = $this->getUserHierarchyLevel($targetUserId);
        
        // Can only moderate users with lower hierarchy level
        return $moderatorLevel > $targetLevel;
    }
    
    public function assignRole($userId, $roleId, $assignedBy) {
        if (!$this->canAssignRole($assignedBy, $roleId)) {
            throw new Exception("No permission to assign this role");
        }
        
        $user = $this->db->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Update primary role
        $this->db->update('users', ['role_id' => $roleId], 'id = ?', [$userId]);
        
        // Log the role change
        $this->db->insert('moderation_logs', [
            'action_type' => 'role_change',
            'target_user_id' => $userId,
            'moderator_id' => $assignedBy,
            'reason' => "Role changed to role ID: $roleId",
            'platform' => 'panel',
            'metadata' => json_encode(['old_role' => $user['role_id'], 'new_role' => $roleId])
        ]);
        
        // Clear cached permissions
        unset($this->userPermissions[$userId]);
        
        return true;
    }
    
    public function addAdditionalRole($userId, $roleId, $assignedBy) {
        if (!$this->canAssignRole($assignedBy, $roleId)) {
            throw new Exception("No permission to assign this role");
        }
        
        $user = $this->db->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        
        if (!in_array($roleId, $additionalRoles)) {
            $additionalRoles[] = $roleId;
            
            $this->db->update('users', 
                ['additional_roles' => json_encode($additionalRoles)], 
                'id = ?', 
                [$userId]
            );
            
            // Log the role addition
            $this->db->insert('moderation_logs', [
                'action_type' => 'role_change',
                'target_user_id' => $userId,
                'moderator_id' => $assignedBy,
                'reason' => "Additional role added: $roleId",
                'platform' => 'panel',
                'metadata' => json_encode(['action' => 'add_additional_role', 'role_id' => $roleId])
            ]);
            
            // Clear cached permissions
            unset($this->userPermissions[$userId]);
        }
        
        return true;
    }
    
    public function removeAdditionalRole($userId, $roleId, $removedBy) {
        $user = $this->db->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $additionalRoles = json_decode($user['additional_roles'], true) ?: [];
        $key = array_search($roleId, $additionalRoles);
        
        if ($key !== false) {
            unset($additionalRoles[$key]);
            $additionalRoles = array_values($additionalRoles); // Reindex array
            
            $this->db->update('users', 
                ['additional_roles' => json_encode($additionalRoles)], 
                'id = ?', 
                [$userId]
            );
            
            // Log the role removal
            $this->db->insert('moderation_logs', [
                'action_type' => 'role_change',
                'target_user_id' => $userId,
                'moderator_id' => $removedBy,
                'reason' => "Additional role removed: $roleId",
                'platform' => 'panel',
                'metadata' => json_encode(['action' => 'remove_additional_role', 'role_id' => $roleId])
            ]);
            
            // Clear cached permissions
            unset($this->userPermissions[$userId]);
        }
        
        return true;
    }
    
    // Define all available permissions in the system
    public static function getAllPermissions() {
        return [
            // Dashboard permissions
            'dashboard.view' => 'View dashboard',
            'dashboard.admin' => 'View admin dashboard statistics',
            
            // Member management
            'members.view' => 'View member list',
            'members.view_details' => 'View detailed member information',
            'members.edit' => 'Edit member profiles',
            'members.delete' => 'Delete members',
            'members.warn' => 'Issue warnings to members',
            'members.kick' => 'Kick members',
            'members.ban' => 'Ban members',
            'members.unban' => 'Unban members',
            
            // Role management
            'roles.view' => 'View roles',
            'roles.assign' => 'Assign roles to users',
            'roles.create' => 'Create new roles',
            'roles.edit' => 'Edit existing roles',
            'roles.delete' => 'Delete roles',
            
            // Team management
            'teams.view' => 'View teams',
            'teams.create' => 'Create new teams',
            'teams.edit' => 'Edit team information',
            'teams.delete' => 'Delete teams',
            'teams.manage_own' => 'Manage own teams (as leader)',
            'teams.manage_members' => 'Manage team members',
            'teams.apply' => 'Apply to teams',
            
            // Applications
            'applications.view' => 'View applications',
            'applications.review' => 'Review and approve/reject applications',
            'applications.manage' => 'Full application management',
            
            // Events
            'events.view' => 'View events',
            'events.create' => 'Create events',
            'events.edit' => 'Edit events',
            'events.delete' => 'Delete events',
            'events.manage' => 'Full event management',
            
            // Communication
            'announcements.view' => 'View announcements',
            'announcements.create' => 'Create announcements',
            'announcements.edit' => 'Edit announcements',
            'announcements.delete' => 'Delete announcements',
            
            // Tickets
            'tickets.view' => 'View support tickets',
            'tickets.create' => 'Create support tickets',
            'tickets.assign' => 'Assign tickets to staff',
            'tickets.resolve' => 'Resolve tickets',
            
            // Discord integration
            'discord.view_status' => 'View Discord status',
            'discord.kick' => 'Kick from Discord',
            'discord.ban' => 'Ban from Discord',
            'discord.manage_roles' => 'Manage Discord roles',
            
            // TeamSpeak integration
            'teamspeak.view_status' => 'View TeamSpeak status',
            'teamspeak.kick' => 'Kick from TeamSpeak',
            'teamspeak.ban' => 'Ban from TeamSpeak',
            'teamspeak.manage_groups' => 'Manage TeamSpeak groups',
            
            // System administration
            'admin.settings' => 'Manage system settings',
            'admin.logs' => 'View system logs',
            'admin.backup' => 'Create system backups',
            'admin.maintenance' => 'Enable maintenance mode',
            
            // Profile management
            'profile.view_own' => 'View own profile',
            'profile.edit_own' => 'Edit own profile',
            'profile.view_others' => 'View other profiles',
            'profile.edit_others' => 'Edit other profiles'
        ];
    }
}

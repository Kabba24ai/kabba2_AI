<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;


// Models
use App\Models\Iam\AccessControl\ModuleCategory;
use App\Models\Iam\AccessControl\Module;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModuleSeeder extends Seeder
{

    protected $moduleList = [];

    public function __construct()
    {

        $this->moduleList = [

            // IAM [Start]
            [
                'module_category_name' => 'IAM',
                'modules' => [
                    [
                        'module_name' => 'personnel',
                        'module_title' => 'Personnel',
                        'model_name' => 'User',
                        'permissions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'delete' => 'Delete',
                        ]
                    ],
                    [
                        'module_name' => 'roles',
                        'module_title' => 'Roles',
                        'model_name' => 'Role',
                        'permissions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'delete' => 'Delete',
                        ]
                    ],
                    [
                        'module_name' => 'modules',
                        'module_title' => 'Modules',
                        'model_name' => 'Module',
                        'permissions' => [
                            'set_permissions' => 'Set Permissions',
                        ]
                    ],
                ]
            ],
            // IAM [End]

            // Product Management [Start]
            [
                'module_category_name' => 'Product Management',
                'modules' => [
                    [
                        'module_name' => 'product_categories',
                        'module_title' => 'Product Categories',
                        'model_name' => 'ProductCategory',
                        'permissions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'delete' => 'Delete',
                        ]
                    ],
                    [
                        'module_name' => 'products',
                        'module_title' => 'Products',
                        'model_name' => 'Product',
                        'permissions' => [
                            'view' => 'View',
                            'add' => 'Add',
                            'edit' => 'Edit',
                            'delete' => 'Delete',
                        ]
                    ],
                ]
            ],
            // Product Management [End]
        ];
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $module_categories_arr = [];
        $modules_arr = [];
        $modules_name_arr = [];


        $role_item = Role::where('name', 'System Architecture')->first();


        foreach ($this->moduleList as $category) {

            $module_category_item = ModuleCategory::firstOrCreate([
                'title' => $category['module_category_name']
            ]);

            $module_categories_arr[] = $module_category_item->title;

            // Find the difference between $collection1 and $collection2
            $existingModule = collect($module_category_item->modules->pluck('title'));
            $modules = collect($category['modules'])->pluck('module_title');
            $difference = $existingModule->diff($modules);
            $removeModules = $difference->all();
            // Output the difference
            if (count($removeModules) > 0) {
                Module::where('module_category_id', $module_category_item->id)->whereIn('title', $removeModules)->delete();
            }


            if (!is_null($module_category_item) && isset($category['modules']) && count($category['modules']) > 0) {
                foreach ($category['modules'] as $module) {

                    $module_item = Module::firstOrCreate([
                        'module_category_id' => $module_category_item->id,
                        'name' => $module['module_name'],
                    ]);
                    $module_item->update([
                        'title' => $module['module_title'],
                        'model_name' => $module['model_name'],
                    ]);

                    $modules_arr[] = $module_item->title;
                    $modules_name_arr[] = $module_item->name;


                    $module_item->permissions = implode(',', array_keys($module['permissions']));

                    if ($module_item->isDirty()) {
                        $module_item->need_set_permissions = 'Yes';
                        $module_item->save();
                    }


                    $permissions_arr = [];
                    $permission_options = [];
                    if (is_array($module['permissions']) && count($module['permissions']) > 0) {
                        foreach ($module['permissions'] as $permission_name => $permission_title) {
                            $permission_name = $module['module_name'] . '.' . $permission_name;
                            $permissions_arr[] = $permission_name;
                            $permission_options[] = $permission_name;
                            $permission_item = Permission::firstOrCreate([
                                'name' => $permission_name,
                                'title' => $permission_title,
                                'guard_name' => 'web',
                            ]);
                            $permission_item->update([
                                'module_id' => $module_item->id
                            ]);
                            $this->setPermissionToSystemArchitecture($role_item, $permission_item);
                        }
                    }
                    $module_item->permission_options = json_encode($permission_options);
                    $module_item->save();

                    // Delete Other Permissions of Module
                    Permission::where('module_id', $module_item->id)->whereNotIn('name', $permissions_arr)->delete();
                }
            }
        }

        // Delete Other Module
        Module::whereNotIn('title', $modules_arr)->delete();
        Module::whereNotIn('name', $modules_name_arr)->delete();
        Module::whereNull('module_category_id')->delete();
        ModuleCategory::whereNotIn('title', $module_categories_arr)->delete();
        Permission::whereNull('module_id')->delete();
    }


    // Assign All permissions to System Architecture [Start]

    private function setPermissionToSystemArchitecture(Role $role_item, Permission $permission_item): void
    {
        if (!is_null($role_item)) {
            $role_item->givePermissionTo($permission_item);
        }
    }

    // Assign All permissions to System Architecture [End]
}

<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'preset_id' => 'nullable|exists:interval_presets,id',
            'tasks' => 'array',
            'tasks.*.task_id' => 'required|exists:service_tasks,id',
            'tasks.*.intervals' => 'required|array'
        ]);
        
        $template = ServiceTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'preset_id' => $request->preset_id
        ]);
        
        if (!empty($request->tasks)) {
            foreach ($request->tasks as $taskData) {
                ServiceTemplateTask::create([
                    'template_id' => $template->id,
                    'task_id' => $taskData['task_id'],
                    'intervals' => $taskData['intervals'] ?? []
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'template' => $template->load(['preset', 'templateTasks.task'])
        ]);
    }
    
    public function show($id)
    {
        $template = ServiceTemplate::with(['preset', 'templateTasks.task.category'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'template' => $template
        ]);
    }
    
    public function destroy($id)
    {
        $template = ServiceTemplate::findOrFail($id);
        $template->templateTasks()->delete();
        $template->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }
    
    public function addTask(Request $request, $templateId)
    {
        $request->validate([
            'task_id' => 'required|exists:service_tasks,id',
            'intervals' => 'nullable|array'
        ]);
        
        $templateTask = ServiceTemplateTask::create([
            'template_id' => $templateId,
            'task_id' => $request->task_id,
            'intervals' => $request->intervals ?? []
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Task added to template',
            'templateTask' => $templateTask->load('task')
        ]);
    }
    
    public function updateTaskIntervals(Request $request, $templateTaskId)
    {
        $request->validate([
            'intervals' => 'required|array'
        ]);
        
        $templateTask = ServiceTemplateTask::findOrFail($templateTaskId);
        $templateTask->update(['intervals' => $request->intervals]);
        
        return response()->json([
            'success' => true,
            'message' => 'Task intervals updated'
        ]);
    }
    
    public function removeTask($templateTaskId)
    {
        $templateTask = ServiceTemplateTask::findOrFail($templateTaskId);
        $templateTask->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Task removed from template'
        ]);
    }
}
<!--begin::Accordion-->
<div class="accordion mb-5 rounded border border-dashed bg-light-{{ $page_instruction_item->color }} border-{{ $page_instruction_item->color }}" id="customAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header" id="customHeadingOne">
      <div class="d-flex position-relative">
      <button class="bg-light-{{ $page_instruction_item->color }} text-gray-800 fw-bolder fs-4 accordion-button w-100 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customCollapseOne" aria-expanded="false" aria-controls="customCollapseOne">
            <span class="me-5">
                <i
                    class="page-notification-icon text-{{ $page_instruction_item->color }} fas {{ $page_instruction_item->icon }}"></i>
            </span>

            {{ $page_instruction_item->title }}

        </button>
        <a type="button"
            class="btn btn-sm temporary-loader-href-click btn-icon z-index-9 btn-{{ $page_instruction_item->color }} position-absolute top-0 start-100 translate-middle"
            data-bs-toggle="modal" data-bs-target="#edit-page-instruction-modal-full-page-instruction">
            <i class="fas fa-pencil-alt"></i>
        </a>
      </div>
    </h2>

    <div id="customCollapseOne" class="accordion-collapse collapse" aria-labelledby="customHeadingOne" data-bs-parent="#customAccordion">
      <div class="accordion-body fs-5 text-gray-600 bg-light-{{ $page_instruction_item->color }}">
        {!! $page_instruction_item->content !!}
      </div>
    </div>

  </div>
</div>
<!--end::Accordion-->


<div class="modal fade" id="edit-page-instruction-modal-full-page-instruction" tabindex="-1" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-normal">Edit Page Instruction</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></div>
            </div>
            {{ html()
                ->modelForm($page_instruction_item, 'PUT', route('admin.cms.page_instruction'))
                ->attributes([
                    'class' => 'w-100',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'id' => 'kt_page_notice',
                ])
                ->open() }}

                {{ html()->hidden('unique_id', $page_instruction_item->unique_id) }}
            <div class="modal-body">
                <div class="fv-row mb-4">
                    <label class="form-label fs-6 text-dark">Title</label>
                    {{ html()->text('title')->attributes(['class' => 'form-control', 'placeholder' => 'Title'])->required() }}
                </div>

                <div class="fv-row mb-4">
                    <label class="form-label fs-6 text-dark">Description</label>
                    {{ html()->textarea('content')->attributes(['class' => 'form-control note-tinymce']) }}
                </div>

                <div class="fv-row row mt-4">
                    <div class="col-md-6">
                        <label class="form-label fs-6 text-dark">Color</label>
                        {{ html()
                            ->select('color',[
                                'primary' => 'Blue',
                                'success' => 'Green',
                                'danger' => 'Red',
                                'warning' => 'Yellow',
                                'dark' => 'Black',
                                'info' => 'Purple',
                            ])->attributes(['class' => 'form-select', 'placeholder' => 'Please select'])->required() }}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-6 text-dark">Icon</label>
                        <div class="clearfix"></div>
                        {{ html()->radio('icon',false, 'fas fa-info-circle')->attributes(['class' => 'btn-check', 'id' => 'kt_icon_info_circle']) }}
                        <label class="btn btn-outline btn-outline-dashed btn-outline-default px-4 py-3 me-2"
                            for="kt_icon_info_circle">
                            <span class="d-block fs-4 text-center">
                                <i class="fs-2 fas fa-info-circle"></i>
                            </span>
                        </label>

                        {{ html()->radio('icon',false, 'fas fa-check-circle')->attributes(['class' => 'btn-check', 'id' => 'kt_icon_check_circle']) }}
                        <label class="btn btn-outline btn-outline-dashed btn-outline-default px-4 py-3 me-2"
                            for="kt_icon_check_circle">
                            <span class="d-block fs-4 text-center">
                                <i class="fs-2 fas fa-check-circle"></i>
                            </span>
                        </label>

                        {{ html()->radio('icon', false,'fas fa-exclamation-circle')->attributes([
                            'class' => 'btn-check',
                            'id' => 'kt_icon_exclamation_circle',
                        ]) }}
                        <label class="btn btn-outline btn-outline-dashed btn-outline-default px-4 py-3 me-2"
                            for="kt_icon_exclamation_circle">
                            <span class="d-block fs-4 text-center">
                                <i class="fs-2 fas fa-exclamation-circle"></i>
                            </span>
                        </label>

                        {{ html()->radio('icon',false, 'fas fa-exclamation-triangle')->attributes([
                            'class' => 'btn-check',
                            'id' => 'kt_icon_exclamation_triangle',
                        ]) }}
                        <label class="btn btn-outline btn-outline-dashed btn-outline-default px-4 py-3 me-2"
                            for="kt_icon_exclamation_triangle">
                            <span class="d-block fs-4 text-center">
                                <i class="fs-2 fas fa-exclamation-triangle"></i>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-end"><button type="submit" id="kt_sign_in_submit" class="btn btn btn-primary"
                    data-kt-indicator="off"><span class="indicator-label">
                        <!----> Submit
                    </span><span class="indicator-progress"> Please wait... <span
                            class="spinner-border spinner-border-sm align-middle ms-2"></span></span></button>
            </div>
            {!! html()->closeModelForm() !!}
        </div>
    </div>
</div>

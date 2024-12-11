@include('Admin.templates.header')

@php
$role = session('role'); // Fetch the role from the session
$roleName = '';

if (!empty($role)) { // Check if the role exists in the session
    switch ($role) {
    case 'superadmin':
    $roleName = 'superadmin';
    break;
    case 'admin':
    $roleName = 'admin';
    break;
    default:
    $roleName = 'default'; // Add a default case if needed
    break;
    }
}
@endphp

<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">Total Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $totalUsers }}</h4>
                                    <p class="text-success mb-2">(+29%)</p>
                                </div>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <div class="mdi mdi-account-outline mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $active }}</h4>
                                    <p class="text-danger mb-2">(-14%)</p>
                                </div>
                                <p class="mb-0">Last week analytics</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <div class="mdi mdi-account-check-outline mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">In-Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $inactive }}</h4>
                                    <p class="text-success mb-2">(+42%)</p>
                                </div>
                                <p class="mb-0">Last week analytics</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <div class="mdi mdi-account-search mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Users List Table -->
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">Search Filter</h5>
                <div class="d-flex justify-content-between align-items-center row py-3 gap-3 gap-md-0">
                    <div class="col-md-4 user_role"></div>
                    <div class="col-md-4 user_plan"></div>
                    <div class="col-md-4 user_status"></div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-users table">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th></th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Plan</th>
                            <th>Billing</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <!-- Offcanvas to add new user -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser"
                aria-labelledby="offcanvasAddUserLabel">
                <div class="offcanvas-header">
                    <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add User</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0 h-100">
                    <form class="add-new-user pt-0" id="addNewUserForm"
                        action="{{ url($roleName . '/add-new-users') }}" method="post">
                        <input type="hidden" id="Id" value="0" name="user_id"/>
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control required" data-bind="name" id="add-user-fullname"
                                placeholder="John Doe" name="userFullname" aria-label="John Doe" />
                            <label for="add-user-fullname">Full Name</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" id="add-user-email" class="form-control required" data-bind="email"
                                placeholder="Email" aria-label="john.doe@example.com" name="userEmail" />
                            <label for="add-user-email">Email</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" id="add-user-contact" class="form-control phone-mask required"
                                maxlength="10" data-bind="mobileNumber" placeholder="Contact Number"
                                aria-label="john.doe@example.com" name="userContact"
                                onkeypress="return isNumberKey(event, this)" />
                            <label for="add-user-contact">Contact</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" id="add-user-company" class="form-control required"
                                placeholder="Web Developer" aria-label="jdoe1" name="companyName" />
                            <label for="add-user-company">Company</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <select id="country" name="country" class="select2 form-select select2Required">
                                <option value="">Select Country</option>
                                <option value="Australia">Australia</option>
                                <option value="Bangladesh">Bangladesh</option>
                                <option value="Belarus">Belarus</option>
                                <option value="Brazil">Brazil</option>
                                <option value="Canada">Canada</option>
                                <option value="China">China</option>
                                <option value="France">France</option>
                                <option value="Germany">Germany</option>
                                <option value="India">India</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Israel">Israel</option>
                                <option value="Italy">Italy</option>
                                <option value="Japan">Japan</option>
                                <option value="Korea">Korea, Republic of</option>
                                <option value="Mexico">Mexico</option>
                                <option value="Philippines">Philippines</option>
                                <option value="Russia">Russian Federation</option>
                                <option value="South Africa">South Africa</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Turkey">Turkey</option>
                                <option value="Ukraine">Ukraine</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="United States">United States</option>
                            </select>
                            <label for="country">Country</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <select id="user-role" class="form-select required" name="user-role">
                                <option value="">Select Roles</option>
                                <option value="subscriber">Subscriber</option>
                                <option value="editor">Editor</option>
                                <option value="maintainer">Maintainer</option>
                                <option value="author">Author</option>
                                <option value="admin">Admin</option>
                            </select>
                            <label for="user-role">User Role</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-4">
                            <select id="user-plan" class="form-select required" name="user-plan">
                                <option value="">Select Plans</option>
                                <option value="basic">Basic</option>
                                <option value="enterprise">Enterprise</option>
                                <option value="company">Company</option>
                                <option value="team">Team</option>
                            </select>
                            <label for="user-plan">Select Plan</label>
                        </div>
                        <button type="button" class="btn btn-primary me-sm-3 me-1 data-submit"
                            onclick="validate_form('addNewUserForm');">Submit</button>
                        <button type="reset" class="btn btn-outline-secondary"
                            data-bs-dismiss="offcanvas">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->
    @include('Admin.templates.footer')

    <script>
        function hideOffcanvas() {
            var myOffcanvas = document.getElementById('offcanvasAddUser');
            var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
            bsOffcanvas.hide();
        }
        function validate_form(form_name) {
            var flag = 0;
            var flag2 = 0;
            var flag1 = 0;
            var reg_match = [];
            reg_match['name'] = /^[a-zA-Z\s]+$/;
            reg_match['pincode'] = /^\d{6}$/;
            reg_match['text'] = /^[a-zA-Z0-9\-\s,.\']+$/;
            reg_match['content'] = /^[a-zA-Z0-9\-\s,.]+$/;
            reg_match['date'] = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;
            reg_match['numberCharacter'] = /^[a-zA-Z0-9_.-]*$/;
            reg_match['datetime'] = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/;
            reg_match['url'] = /^(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:/~+#-]*[\w@?^=%&amp;/~+#-])?$/;
            reg_match['number'] = /^[0-9]/;
            reg_match['duration'] = /^[1-9]\d*$/;
            reg_match['decimal'] = /^\s*-?[1-9]\d*(\.\d{1,2})?\s*$/;
            reg_match['password'] = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=(?:[^@$!%*?&#]*[@$!%*?&#]){1,3})([A-Za-z\d@$!%*?&#]){8,}$/;
            reg_match['mobileNumber'] = /^[0-9]{10}$/;
            reg_match['helpLineNumber'] = /^\+?\d{1,3}[\s-]?\d{3}[\s-]?\d{3,4}$/;
            reg_match['email'] = /^\w+([-+.'][^\s]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
            $(".err_warning").each(function (index) {
                $(this).remove();
            });
            $(".errborder").each(function (index) {
                $(this).removeClass("errborder");
            });
            $('#' + form_name + ' input,#' + form_name + ' select,#' + form_name + ' textarea,#' + form_name + ' checkbox,#' + form_name + ' date').each(function (index) {

                flag2 = 0;
                var input = $(this);
                if (input.hasClass('required') && $.trim(input.val()) == '') {
                    flag2 = 1;
                    flag = 1;
                    if (input.parent().hasClass('input-group')) {
                        // If the input is inside an input-group, append the error message to the input-group div
                        input.parent().after("<div class='err_warning blink'>*Required " + input.attr('placeholder') + "</div>");
                    } else {
                        // If not, append the error message after the input element
                        input.after("<div class='err_warning blink'>*Required " + input.attr('placeholder') + "</div>");
                    }
                    $(input).addClass("errborder");
                }

                if (input.attr('data-bind') && flag2 == 0) {
                    var str = input.val();
                    var temp1 = new RegExp(reg_match[input.attr('data-bind')]);
                    if ($.trim(str) != '') {
                        if (!temp1.test(str)) {
                            flag1 = 1;
                            if (input.parent().hasClass('input-group')) {
                                // If the input is inside an input-group, append the error message to the input-group div
                                input.parent().after("<div class='err_warning blink'>*Invalid Field " + input.attr('placeholder') + "</div>");
                            } else {
                                // If not, append the error message after the input element
                                input.after("<div class='err_warning blink'>*Invalid Field " + input.attr('placeholder') + "</div>");
                            }
                            $(input).addClass("errborder");
                        }
                    }
                }

                if (input.attr('type') == 'file') {
                    var file_selected = $(input).get(0).files;
                    if (input.hasClass('required')) {
                        if (file_selected.length == 0) {
                            flag1 = 1;
                            $(input).after("<div class='err_warning blink'>*Invalid Field " + input.attr('placeholder') + "</div>");
                            $(input).addClass("errborder");
                        } else if (file_selected.length > 5) {
                            flag1 = 1;
                            $(input).after("<div class='err_warning blink'>*Invalid Field " + input.attr('placeholder') + "</div>");
                            $(input).addClass("errborder");
                        }
                    }
                }

                if (input.attr('type') == 'checkbox' && !$('input[name="' + input.attr('name') + '"]:checked').length) {
                    flag2 = 1;
                    flag = 1;
                    $(input).after("<div class='err_warning blink'>*Required " + input.attr('placeholder') + "</div>");
                    $(input).addClass("errborder");
                }

                if (input.attr('type') == 'select') {
                    var str = input.val();
                    if (input.hasClass('required') && (str == '' || str == 0) && !input.hasClass('errborder')) {
                        flag1 = 1;
                        if (input.data('select2')) {
                            $(input).select2({ containerCssClass: "errborder" });
                            $(input).appendTo("<div class='err_warning blink'>*Required " + input.attr('placeholder') + "</div>");
                        } else {
                            $(input).after("<div class='err_warning blink'>*Required " + input.attr('placeholder') + "</div>");
                            $(input).addClass("errborder");
                        }
                    }
                }

                if (input.hasClass('select2') && input.hasClass('select2Required')) {
                    var getId = $(this).attr('id');
                    var getAttribute = $(this).attr('multiple');
                    var inputValue = $.trim($("#" + getId).val());

                    // Check if the error container exists
                    var errorContainer = $("#" + getId).parent().find(".error-container");

                    if (inputValue === '' || parseInt(inputValue) === 0) {
                        $(".error-container").css("margin-top", "11px");
                        flag2 = 1;
                        flag = 1;

                        var errorMessage = "<div class='err_warning blink'>*Required " + $("#" + getId).attr('placeholder') + "</div>";

                        // If the error container exists, just update the error message
                        if (errorContainer.length > 0) {
                            errorContainer.html(errorMessage);
                        } else {
                            // If the error container doesn't exist, create it and append the error message
                            $("#" + getId).parent().append("<div class='error-container'>" + errorMessage + "</div>");
                        }

                        // Add error classes to the input and the select2 container
                        $("#" + getId).addClass("errborder");
                        if (getAttribute == 'multiple') {
                            $(".error-container").css("margin-top", "11px");
                            var select2Container = $("#" + getId).parent().find(".select2-container--default .select2-selection--multiple");
                            select2Container.addClass("errborder");
                        } else {
                            $(".error-container").css("margin-top", "11px");
                            var select2Container = $("#select2-" + getId + "-container");
                        }

                    } else {
                        // Reset or remove error classes
                        $("#" + getId).removeClass("errborder");
                        $("#select2-" + getId + "-container").removeClass("errborder");

                        // Clear error messages in the associated error container
                        if (errorContainer.length > 0) {
                            errorContainer.html("");
                        }
                    }
                }
            });
            if (flag == 1 || flag1 == 1) {
                efCount = 0;
                $(".errborder").each(function (index) {
                    if (efCount == 0)
                        $(this).focus();
                    efCount++;
                });
                return false;
            } else {
                var form = $("#" + form_name);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    async: false,
                    dataType: 'json',
                    url: form.attr("action"),
                    method: form.attr("method"),
                    data: form.serialize(),
                    success: function (result) {
                        if (result.status != 1) {
                            if (result.validation) {
                                if (result.validation.userEmail) {
                                    $('#add-user-email').after("<div class='err_warning blink'>*Invalid Field Email <br/>" + result.validation.userEmail[0] + "</div>");
                                    $('#add-user-email').addClass("errborder");
                                }

                                if (result.validation.userContact) {
                                    $('#add-user-contact').after("<div class='err_warning blink'>*Invalid Field Contact Number <br/>" + result.validation.userContact[0] + "</div>");
                                    $('#add-user-contact').addClass("errborder");
                                }
                            }

                            setTimeout(function () {
                                $(".card-title-desc").css('display', 'none');
                                // document.getElementById(form_name).reset();
                            }, 3000);
                        } else if (result.status == 1) {

                            // Close offcanvas before Ajax
                            var myOffcanvas = document.getElementById('offcanvasAddUser');
                            var bsOffcanvas = bootstrap.Offcanvas.getInstance(myOffcanvas) || new bootstrap.Offcanvas(myOffcanvas);
                            bsOffcanvas.hide();

                            Swal.fire({ icon: "success", title: result.message, showConfirmButton: false, timer: 1500 });
                            setTimeout(function () {
                                location.reload();
                            }, 3000);
                        }
                    }
                });
            }
        }

        function editRecord(id) {
            $.ajax({
                async: false,
                dataType: 'json',
                url: "{{ url($roleName . '/users/get-details') }}", // Make sure roleName is correctly passed
                method: 'POST',
                data: {
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
                },
                success: function (data) {
                    console.log(data);
                    if (data.status !== 1) {
                        console.error(data.message);
                    } else {
                        // Populate the form with fetched data
                        $("#Id").val(data.id);
                        $("#add-user-fullname").val(data.name);
                        $("#add-user-email").val(data.email);
                        $("#add-user-contact").val(data.contact_no);
                        $("#add-user-company").val(data.company_name);
                        $("#country").val(data.country).trigger('change');
                        $("#user-role").val(data.roles).trigger('change');
                        $("#user-plan").val(data.plan).trigger('change');

                        // Open off-canvas after data is populated
                        var myOffcanvas = document.getElementById('offcanvasAddUser');
                        var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
                        bsOffcanvas.show(); // Assuming 'offcanvasAddUser' is the ID of your off-canvas container
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }


    </script>

<!-- Footer -->
<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl">
        <div class="footer-container d-flex align-items-center justify-content-between py-3 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
                ©
                <script>
                    document.write(new Date().getFullYear());
                </script>
                , made with <span class="text-danger"><i class="tf-icons mdi mdi-heart"></i></span> by
                <a href="https://pixinvent.com" target="_blank" class="footer-link fw-medium">Pixinvent</a>
            </div>
            <div class="d-none d-lg-inline-block">
                <a href="https://themeforest.net/licenses/standard" class="footer-link me-4" target="_blank">License</a>
                <a href="https://1.envato.market/pixinvent_portfolio" target="_blank" class="footer-link me-4">More
                    Themes</a>

                <a href="https://demos.pixinvent.com/materialize-html-admin-template/documentation/" target="_blank"
                    class="footer-link me-4">Documentation</a>

                <a href="https://pixinvent.ticksy.com/" target="_blank"
                    class="footer-link d-none d-sm-inline-block">Support</a>
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->

<div class="content-backdrop fade"></div>
</div>
<!-- Content wrapper -->
</div>
<!-- / Layout page -->
</div>

<!-- Overlay -->
<div class="layout-overlay layout-menu-toggle"></div>

<!-- Drag Target Area To SlideIn Menu On Small Screens -->
<div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->

<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="../../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../../assets/vendor/libs/popper/popper.js"></script>
<script src="../../assets/vendor/js/bootstrap.js"></script>
<script src="../../assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../../assets/vendor/libs/hammer/hammer.js"></script>
<script src="../../assets/vendor/libs/i18n/i18n.js"></script>
<script src="../../assets/vendor/libs/typeahead-js/typeahead.js"></script>
<script src="../../assets/vendor/js/menu.js"></script>

<!-- endbuild -->

<!-- Vendors JS -->
<script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="../../assets/vendor/libs/swiper/swiper.js"></script>

<!-- Vendors JS -->
<script src="../../assets/vendor/libs/moment/moment.js"></script>
{{-- <script src="../../assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script> --}}
<script src="../../assets/vendor/libs/select2/select2.js"></script>
<script src="../../assets/vendor/libs/@form-validation/umd/bundle/popular.min.js"></script>
<script src="../../assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js"></script>
<script src="../../assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js"></script>
<script src="../../assets/vendor/libs/cleavejs/cleave.js"></script>
<script src="../../assets/vendor/libs/cleavejs/cleave-phone.js"></script>

{{-- <script src="{{ asset('admin/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script> --}}

<!-- Responsive Examples -->
{{-- <script src="{{ asset('admin/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script> --}}

<!-- Buttons Examples -->
{{-- <script src="{{ asset('admin/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('admin/assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script> --}}

<!-- Extra Tables Plugins -->
{{-- <script src="{{ asset('admin/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script> --}}
{{-- <script src="{{ asset('admin/assets/libs/datatables.net-select/js/dataTables.select.min.js') }}"></script> --}}


<!-- Main JS -->
<script src="../../assets/js/main.js"></script>

<!-- Page JS -->
<script src="../../assets/js/dashboards-analytics.js"></script>

@if (isset($assetsJs) && !empty($assetsJs))
@foreach ($assetsJs as $assetsJsDetails)
<script src="{{ asset('assets/js/' . $assetsJsDetails . '.js') }}"></script>
@endforeach
@endif

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Parameter mein 'btnElement = null' add kiya hai taaki click ki hui row ka reference mil sake
    // function updateStatus(id, status, name, tableName, btnElement = null) {
    //     // 🛠️ FIX: Agar tableName encoded hai (jaise 'Mg==' ya kuch aur), toh usko decode karo
    //     try {
    //         let decoded = atob(tableName);
    //         if (!isNaN(decoded) || decoded === 'users' || decoded === 'user') {
    //             if (!isNaN(decoded)) {
    //                 tableName = 'users';
    //             } else {
    //                 tableName = decoded;
    //             }
    //         }
    //     } catch (e) {
    //         // Fallback agar plain text ho
    //     }
        
    //     let icon = 'info';
    //     let title = 'Are you sure?';
    //     let text = "Once deleted, you will not be able to recover!";
    //     let confirmButtonText = 'Yes';
    //     let successtitle = '';
    //     let successttext = '';
        
    //     status = parseInt(status);
        
    //     switch (status) {
    //         case 1:
    //             icon = 'success';
    //             title = 'Are you sure?';
    //             text = "Activate The Record!";
    //             confirmButtonText = 'Activate';
    //             successtitle = 'Activated';
    //             successttext = 'Record Activated Successfully....!';
    //             break;
    //         case 2:
    //             icon = 'error';
    //             title = 'Are you sure?';
    //             text = "Permanently Remove Record...!";
    //             confirmButtonText = 'Remove';
    //             successtitle = 'Deleted';
    //             successttext = 'Record Permanently Removed Successfully....!';
    //             break;
    //         default:
    //             icon = 'warning';
    //             title = 'Are you sure?';
    //             text = "Deactivate The Record!";
    //             confirmButtonText = 'Deactivate';
    //             successtitle = 'Deactivated';
    //             successttext = 'Record Deactivated Successfully....!';
    //             break;
    //     }

    //     Swal.fire({
    //         title: title,
    //         text: text,
    //         icon: icon,
    //         showCancelButton: true,
    //         confirmButtonText: confirmButtonText,
    //         cancelButtonText: "No, cancel!",
    //         confirmButtonClass: "btn btn-success mt-2",
    //         cancelButtonClass: "btn btn-danger ms-2 mt-2",
    //         buttonsStyling: false
    //     }).then(function (t) {
    //         if (t.value) {
    //             $.ajax({
    //                 headers: {
    //                     'X-CSRF-TOKEN': csrfToken
    //                 },
    //                 url: "{{ route('status.change') }}",
    //                 type: 'post',
    //                 dataType: 'json',
    //                 data: { id: id, status: status, name: name },
    //                 success: function (result1) {
    //                     if (result1.success == "1" || result1.success == 1 || result1.status == 1) {
    //                         Swal.fire({
    //                             title: successtitle,
    //                             text: successttext,
    //                             icon: "success",
    //                             timer: 1500,
    //                             showConfirmButton: false
    //                         });

    //                         setTimeout(function() {
    //                             let tableSelector = '';
    //                             switch (tableName) {
    //                                 case 'users':
    //                                     tableSelector = '#usersTable';
    //                                     break;
    //                                 default:
    //                                     tableSelector = '#' + tableName + 'Table';
    //                                     break;
    //                             }

    //                             if ($.fn.DataTable.isDataTable(tableSelector)) {
    //                                 let table = $(tableSelector).DataTable();
                                    
    //                                 // 👉 Agar delete hai, toh row hataane ke sath ajax reload bhi karo taaki entries count update ho jaye
    //                                 if (status === 2 && btnElement) {
    //                                     table.row($(btnElement).closest('tr')).remove().draw(false);
    //                                     // Entries count aur pagination ko sync karne ke liye background reload
    //                                     table.ajax.reload(null, false);
    //                                 } else {
    //                                     table.ajax.reload(null, false);
    //                                 }
    //                             } else if ($.fn.DataTable.isDataTable('#usersTable')) {
    //                                 $('#usersTable').DataTable().ajax.reload(null, false);
    //                             } else {
    //                                 location.reload();
    //                             }
    //                         }, 300);
    //                     } else {
    //                         Swal.fire("Error", "Something went wrong on the server!", "error");
    //                     }
    //                 },
    //                 error: function(xhr) {
    //                     console.log("AJAX Error:", xhr.responseText);
    //                     Swal.fire("Error", "Could not process request.", "error");
    //                 }
    //             });
    //         } else {
    //             Swal.fire({
    //                 title: "Cancelled",
    //                 text: "Your record is safe :)",
    //                 icon: "error"
    //             });
    //         }
    //     });
    // }

    function updateStatus(id, status, name, tableName, btnElement = null) {
        // 🛠️ FIX: Agar tableName encoded hai (jaise 'Mg==' ya kuch aur), toh usko decode karo
        try {
            let decoded = atob(tableName);
            if (!isNaN(decoded) || decoded === 'users' || decoded === 'user') {
                if (!isNaN(decoded)) {
                    tableName = 'users';
                } else {
                    tableName = decoded;
                }
            }
        } catch (e) {
            // Fallback agar plain text ho
        }

        console.log("Final Table Name for Selector:", tableName);
        console.log("Status:", status);
        
        let icon = 'info';
        let title = 'Are you sure?';
        let text = "Once deleted, you will not be able to recover!";
        let confirmButtonText = 'Yes';
        let successtitle = '';
        let successttext = '';
        
        status = parseInt(status);
        
        switch (status) {
            case 1:
                icon = 'success';
                title = 'Are you sure?';
                text = "Activate The Record!";
                confirmButtonText = 'Activate';
                successtitle = 'Activated';
                successttext = 'Record Activated Successfully....!';
                break;
            case 2:
                icon = 'error';
                title = 'Are you sure?';
                text = "Permanently Remove Record...!";
                confirmButtonText = 'Remove';
                successtitle = 'Deleted';
                successttext = 'Record Permanently Removed Successfully....!';
                break;
            default:
                icon = 'warning';
                title = 'Are you sure?';
                text = "Deactivate The Record!";
                confirmButtonText = 'Deactivate';
                successtitle = 'Deactivated';
                successttext = 'Record Deactivated Successfully....!';
                break;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: "No, cancel!",
            confirmButtonClass: "btn btn-success mt-2",
            cancelButtonClass: "btn btn-danger ms-2 mt-2",
            buttonsStyling: false
        }).then(function (t) {
            if (t.value) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    url: "{{ route('status.change') }}",
                    type: 'post',
                    dataType: 'json',
                    data: { id: id, status: status, name: name },
                    success: function (result1) {
                        if (result1.success == "1" || result1.success == 1 || result1.status == 1) {
                            Swal.fire({
                                title: successtitle,
                                text: successttext,
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false
                            });

                            setTimeout(function() {
                                let tableSelector = '';
                                switch (tableName) {
                                    case 'users':
                                        tableSelector = '#usersTable';
                                        break;
                                    default:
                                        tableSelector = '#' + tableName + 'Table';
                                        break;
                                }

                                if ($.fn.DataTable.isDataTable(tableSelector)) {
                                    let table = $(tableSelector).DataTable();
                                    
                                    // 👉 Yeh server se naya data fetch karke rows aur entries/pagination count dono ko update kar dega
                                    table.ajax.reload(null, false);
                                    
                                } else if ($.fn.DataTable.isDataTable('#usersTable')) {
                                    $('#usersTable').DataTable().ajax.reload(null, false);
                                } else {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            Swal.fire("Error", "Something went wrong on the server!", "error");
                        }
                    },
                    error: function(xhr) {
                        console.log("AJAX Error:", xhr.responseText);
                        Swal.fire("Error", "Could not process request.", "error");
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Your record is safe :)",
                    icon: "error"
                });
            }
        });
    }
    
    function isNumberKey(evt, element) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57) && !(charCode == 46 || charCode == 8)) {
            return false;
        } else {
            var len = $(element).val().length;
            var index = $(element).val().indexOf('.');
            if (index > 0 && charCode == 46) {
                return false;
            }
            if (index > 0 && len - index > 2) {
                return false;
            }
        }
        return true;
    }

    function ActivateUser(table, key, value, status) {
        var url = "{{ route('send.link', ['value' => ':value']) }}".replace(':value', value);
        icon = 'info';
        dangerMode = true;
        title = 'Are you sure?';
        text = "Once deleted, you will not be able to recover!";
        subtitle = 'Cancel';
        confirmButtonText = 'Yes';
        successtitle = '';
        successttext = '';
        status1 = 1;
        switch (status1) {
            case 1:
                icon = 'success';
                dangerMode = true;
                title = 'Are you sure?';
                text = "Send Activate Link Again!";
                confirmButtonText = 'Send';
                successtitle = 'Sent';
                successttext = 'Link Sent On Registered Email Successfully....!';
                break;
            default:

                break;
        }
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: !0,
            confirmButtonText: confirmButtonText,
            cancelButtonText: "No, cancel!",
            confirmButtonClass: "btn btn-success mt-2",
            cancelButtonClass: "btn btn-danger ms-2 mt-2",
            buttonsStyling: !1
        }).then(function (t) {
            if (t.value) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                    },
                    url: url,
                    type: 'post',
                    async: false,
                    data: { value: value, }, // get all form variables
                    success: function (result1) {
                    console.log(table);
                        if (result1 == 1) {
                            // Success case
                            Swal.fire({
                                title: 'Success Title', // Replace with your success title
                                text: 'Success Text',   // Replace with your success text
                                icon: 'success'
                            });
                            
                        } else {
                            // Error case
                            Swal.fire({
                                title: 'Error Title', // Replace with your error title
                                text: 'Error Text',   // Replace with your error text
                                icon: 'error'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle errors from the AJAX request itself
                        Swal.fire({
                            title: 'Server Error', // Replace with your error title
                            text: 'An unexpected error occurred: ' + error,
                            icon: 'error'
                        });
                    }

                });
            } else {
                t.dismiss;
                Swal.fire({ title: "Cancelled", text: "Your record is safe :)", icon: "error" })
            }
        });
    }

    function goBackToListing() {
        $(".err_warning").each(function (index) {
            $(this).remove();
        });
        $(".errborder").each(function (index) {
            $(this).removeClass("errborder");
        });

        $('.select2').val([]).trigger('change');
        $("html, body").animate({ scrollTop: 0 }, "slow");
    }
</script>
</body>

</html>

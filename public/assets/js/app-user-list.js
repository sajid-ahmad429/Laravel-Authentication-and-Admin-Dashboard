/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_user_table = $('.datatables-users'),
    select2 = $('.select2'),
    userView = 'app-user-view-account.html',
    statusObj = {
      2: { title: 'Deleted', class: 'bg-label-warning' },
      1: { title: 'Active', class: 'bg-label-success' },
      0: { title: 'Inactive', class: 'bg-label-secondary' }
    };

  if (select2.length) {
    var $this = select2;
    select2Focus($this);
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      ajax: assetsPath + 'json/user-list.json', // JSON file to add data
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'full_name' },
        { data: 'full_name' },
        { data: 'role' },
        { data: 'current_plan' },
        { data: 'country' },
        { data: 'status' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // For Checkboxes
          targets: 1,
          orderable: false,
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          }
        },
        {
          // User full name and email
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['full_name'],
              $email = full['email'],
              $image = full['avatar'];
            if ($image) {
              // For Avatar image
              var $output =
                '<img src="' + assetsPath + 'img/avatars/' + $image + '" alt="Avatar" class="rounded-circle">';
            } else {
              // For Avatar badge
              var stateNum = Math.floor(Math.random() * 6);
              var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
              var $state = states[stateNum],
                $name = full['full_name'],
                $initials = $name.match(/\b\w/g) || [];
              $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
              $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';
            }
            // Creates full output for row
            var $row_output =
              '<div class="d-flex justify-content-start align-items-center user-name">' +
              '<div class="avatar-wrapper">' +
              '<div class="avatar avatar-sm me-3">' +
              $output +
              '</div>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<a href="' +
              userView +
              '" class="text-truncate"><span class="fw-medium text-heading">' +
              $name +
              '</span></a>' +
              '<small>' +
              $email +
              '</small>' +
              '</div>' +
              '</div>';
            return $row_output;
          }
        },
        {
          // User Role
          targets: 3,
          render: function (data, type, full, meta) {
            var $role = full['role'];
            var roleBadgeObj = {
              Subscriber: '<i class="mdi mdi-account-outline mdi-20px text-primary me-2"></i>',
              Author: '<i class="mdi mdi-cog-outline mdi-20px text-warning me-2"></i>',
              Maintainer: '<i class="mdi mdi-chart-donut mdi-20px text-success me-2"></i>',
              Editor: '<i class="mdi mdi-pencil-outline mdi-20px text-info me-2"></i>',
              Admin: '<i class="mdi mdi-laptop mdi-20px text-danger me-2"></i>'
            };
            return "<span class='text-truncate d-flex align-items-center'>" + roleBadgeObj[$role] + $role + '</span>';
          }
        },
        {
          // Plans
          targets: 4,
          render: function (data, type, full, meta) {
            var $plan = full['current_plan'];
            console.log($plan);
            return '<span class="text-heading">' + $plan + '</span>';
          }
        },
        {
          // User Status
          targets: 6,
        //   render: function (data, type, full, meta) {
        //     var $status = full['status'];

        //     return (
        //       '<span class="badge rounded-pill ' +
        //       statusObj[$status].class +
        //       '" text-capitalized>' +
        //       statusObj[$status].title +
        //       '</span>'
        //     );
        //   }
            render: function (data, type, full, meta) {
                var status = full['status'];
                var id = full['id']; // Assuming 'id' is available in the data
                var encodedId = btoa(id); // Base64 encode the ID
                var encodedType = btoa('users'); // Base64 encode 'master_account'

                if (status == 1) {
                    return (
                        '<button class="badge bg-label-success btn btn-sm" ' +
                        'onclick="updateStatus(\'' + encodedId + '\', 0, \'' + encodedType + '\')">Active</button>'
                    );
                } else if (status == 0) {
                    return (
                        '<button class="badge bg-label-secondary btn btn-sm" ' +
                        'onclick="updateStatus(\'' + encodedId + '\', 1, \'' + encodedType + '\')">Inactive</button>'
                    );
                }
            }
        },
        {
            // Actions
            targets: -1,
            title: 'Actions',
            searchable: false,
            orderable: false,
            render: function (data, type, full, meta) {
                var userId = full['id']; // Directly use data for ID
                var activated = full['activated']; // Get the user's activation status
                var activateButton = "";

                if (activated == 0) {
                    activateButton += '<a href="javascript:;" class="dropdown-item" title="Send activation link" ' +
                    'onclick="ActivateUser(\'' + btoa('users') + '\', \'' + btoa(userId) + '\', \'' + btoa(userId) + '\', \'' + btoa(2) + '\')">' +
                    '<i class="mdi mdi-shield-check me-2"></i><span>Send Activation Link</span></a>';
                }

                // Ensure that 'userView' contains the correct URL
                return (
                    '<div class="d-inline-block text-nowrap">' +
                    '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
                    '<i class="mdi mdi-dots-vertical mdi-20px"></i></button>' +
                    '<div class="dropdown-menu dropdown-menu-end m-0">' +
                    // Passing the Base64 encoded userId to the edit function
                    '<a href="javascript:;" class="dropdown-item" onclick="editRecord(\'' + btoa(userId) + '\')">' +
                    '<i class="mdi mdi-pencil-outline me-2"></i><span>Edit</span></a>' +
                    // Passing the Base64 encoded userId and other parameters to the updateStatus function
                    '<a href="javascript:;" class="dropdown-item" onclick="updateStatus(\'' + btoa(userId) + '\', 2, \'' + btoa('users') + '\', \'' + btoa(2) + '\')">' +
                    '<i class="mdi mdi-delete-outline me-2"></i><span>Delete</span></a>' +
                    activateButton + // Correct placement of the activate button
                    '</div>' +
                    '</div>'
                );
            }
        }
      ],
      order: [[2, 'asc']],
      dom:
        '<"row mx-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0 gap-3"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: 'Show _MENU_',
        search: '',
        searchPlaceholder: 'Search..'
      },
      // Buttons with Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle me-3',
          text: '<i class="mdi mdi-export-variant me-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="mdi mdi-printer-outline me-1" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be print
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                //customize print view for dark
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="mdi mdi-file-document-outline me-1" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="mdi mdi-file-excel-outline me-1"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            //   customize: function (xlsx) {

            //     var sheet = xlsx.xl.worksheets['sheet1.xml'];

            //     var rows = sheet.getElementsByTagName('row');



            //     // Loop through rows

            //     for (var i = 0; i < rows.length; i++) {

            //         var row = rows[i];

            //         var cells = row.getElementsByTagName('c');



            //         // Check if it's the first row (index 0) which is the title

            //          if (i === 1) {

            //             for (var j = 0; j < cells.length; j++) {

            //                 var cell = cells[j];



            //                 // Check if it's within the range A2 to F2 (header cells)

            //                 if (j >= 0 && j <= 5) {

            //                     var is = cell.getElementsByTagName('is')[0];

            //                     var t = is.getElementsByTagName('t')[0];



            //                     // Apply custom style to these cells (background color)

            //                     cell.setAttribute('s', '42'); // Apply custom style



            //                     // Customize cell content based on column index

            //                     if (j === 0) {

            //                         t.textContent = 'Sr. No.';

            //                     } else if (j === 1) {

            //                         t.textContent = 'User';

            //                     } else if (j === 2) {

            //                         t.textContent = 'Role';

            //                     } else if (j === 3) {

            //                         t.textContent = 'Contact No.';

            //                     } else if (j === 4) {

            //                         t.textContent = 'Registered Date';

            //                     } else if (j === 5) {

            //                         t.textContent = 'Status';

            //                     }



            //                     // Center-align the text in the header cells

            //                     var r = t.getAttribute('r');

            //                     if (!r) {

            //                         r = '1';

            //                     }

            //                     r = r.replace(/<r>/, '<r><center/>');

            //                     t.setAttribute('r', r);

            //                 }

            //             }

            //         }

            //     }

            // }
            },
            {
              extend: 'pdf',
              text: '<i class="mdi mdi-file-pdf-box me-1"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="mdi mdi-content-copy me-1"></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        },
        {
          text: '<i class="mdi mdi-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add User</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddUser'
          }
        }
      ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
        // Adding role filter once table initialized
        this.api()
          .columns(3)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserRole" class="form-select text-capitalize"><option value=""> Select Role </option></select>'
            )
              .appendTo('.user_role')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append('<option value="' + d + '">' + d + '</option>');
              });
          });
        // Adding plan filter once table initialized
        this.api()
          .columns(4)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserPlan" class="form-select text-capitalize"><option value=""> Select Plan </option></select>'
            )
              .appendTo('.user_plan')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

              column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                d = String(d).trim(); // Ensure no extra spaces
                if (d !== "") { // Only add non-empty values
                  console.log("Plan Found:", d); // Debugging output
                  select.append('<option value="' + d + '">' + d + '</option>');
                }
              });

          });
        // Adding status filter once table initialized
        this.api()
          .columns(6)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="FilterTransaction" class="form-select text-capitalize"><option value=""> Select Status </option></select>'
            )
              .appendTo('.user_status')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append(
                  '<option value="' +
                    statusObj[d].title +
                    '" class="text-capitalize">' +
                    statusObj[d].title +
                    '</option>'
                );
              });
          });
      }
    });
  }

  // Delete Record
  $('.datatables-users tbody').on('click', '.delete-record', function () {
    dt_user.row($(this).parents('tr')).remove().draw();
  });

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});


// Validation & Phone mask

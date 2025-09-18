// Punto de entrada que inicializa todos los módulos JS al cargar el DOM
import './main';
import './bootstrap';
import '../css/style.css';
import '../css/personalStyles.css';

// CSS de Tom Select cargado globalmente
import "tom-select/dist/css/tom-select.css";

// Módulos compartidos
import { initModals } from './modules/shared/modals';
import { setupRowSelection } from './modules/shared/tableSelection';
import setupTableToggle from './modules/shared/tableToggle';
import { initTomSelects } from './modules/shared/tomselect';

// Módulo actividades
import { initPlannerActions } from './modules/planner/activityActions';
import { setupExportListModal } from "./modules/planner/exportListModal";

// Módulo empleados
import { setupEmployeeCreate, setupEmployeeEdit, setupEmployeeDelete } from './modules/employees/employeeActions';
import { setupEmployeePrint } from './modules/employees/employeePrint';
import './modules/employees/signature';

// Módulo controles
import { setupCheckAttendance } from "./modules/check/checkActions";
import { setupFinalizeActions } from "./modules/check/finalizeActions";
import "./modules/check/activitySearch";
import { setupAttendancePrint } from './modules/check/attendancePrint';
import { setupFacilitatorSignature } from './modules/check/facilitatorSignature';
import { setupAudienceCounter } from './modules/employees/audienceCounter';

document.addEventListener('DOMContentLoaded', () => {
    // --- Módulos generales ---
    initModals();
    initPlannerActions();
    initTomSelects();

    const createForm = document.getElementById('createActivityForm');
    if (createForm) {
        setupAudienceCounter(createForm);
    }

    setupAudienceCounter(document);


    // --- Actividades (solo si la ruta contiene /planner) ---
    if (window.location.pathname.includes('/planner')) {
        setupRowSelection('activitiesTable');
        setupTableToggle('btn-double', 'card-body-table');
        setupExportListModal();
    }

    // --- Empleados (solo si la ruta contiene /employees) ---
    if (window.location.pathname.includes('/employees')) {
        setupEmployeeCreate();
        setupEmployeeEdit();
        setupEmployeeDelete();
        setupEmployeePrint();
        // Selección de filas empleados
        setupRowSelection('employeesTable');
        // Botón ver más/ver menos empleados
        setupTableToggle('btn-double-employees', 'card-body-table-employees');
    }

    // --- Controles (solo si la ruta contiene /check) ---
    if (window.location.pathname.includes('/check')) {
        setupCheckAttendance();
        setupFinalizeActions();
        setupAttendancePrint();
        setupFacilitatorSignature();
        setupTableToggle('btn-double-check', 'card-body-table-check');
    }
});


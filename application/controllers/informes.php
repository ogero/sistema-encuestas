<?php

/**
 * 
 */
class Informes extends CI_Controller{
  
  var $data=array(); //datos para mandar a las vistas

  function __construct() {
    parent::__construct();
    $this->load->library(array('session', 'ion_auth', 'form_validation'));
    //doy formato al mensaje de error de validación de formulario
    $this->form_validation->set_error_delimiters('<span class="label label-important">', '</span>');
    $this->data['usuarioLogin'] = $this->ion_auth->user()->row();
  }
  
  /*
   * Obtener datos de una seccion para mostrar en el informe por materia
   * Última revisión: 2012-02-09 7:32 p.m.
   */
  private function _dameDatosSeccionMateria($seccion, $encuesta, $idDocente, $idMateria, $idCarrera){
      $items = $seccion->listarItemsCarrera($idCarrera);
      $datos_items = array();
      foreach ($items as $k => $item) {
        switch ($item->tipo) {
        case 'S': case 'M': case 'N':
          $datos_respuestas = $encuesta->respuestasPreguntaMateria($item->idPregunta, $idDocente, $idMateria, $idCarrera);
          break;
        case 'T': case 'X':
          $datos_respuestas = $encuesta->textosPreguntaMateria($item->idPregunta, $idMateria, $idCarrera);
          break;
        }
        $datos_items[$k] = array(
          'item' => $item,
          'respuestas' => $datos_respuestas
        );
      }
      return $datos_items;
  }
  
  /*
   * Solicitar y mostrar un informe por materia
   * Última revisión: 2012-02-09 7:39 p.m.
   */
  public function materia(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos','jefes_departamentos','directores','docentes'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idMateria','Materia','required|is_natural_no_zero');
    $this->form_validation->set_rules('idCarrera','Carrera','required|is_natural_no_zero');
    $this->form_validation->set_rules('idEncuesta','Encuesta','required|is_natural_no_zero');
    $this->form_validation->set_rules('idFormulario','Formulario','required|is_natural_no_zero');
    if($this->form_validation->run()){
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      $idMateria = (int)$this->input->post('idMateria');
      $idCarrera = (int)$this->input->post('idCarrera');
      $indicesDocentes = (bool)$this->input->post('indicesDocentes');
      $indicesSecciones = (bool)$this->input->post('indicesSecciones');
      $indiceGlobal = (bool)$this->input->post('indiceGlobal');
      $graficos = (bool)$this->input->post('graficos');
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Usuario');
      $this->load->model('Materia');
      $this->load->model('Carrera');
      $this->load->model('Departamento');
      $this->load->model('Formulario');
      $this->load->model('Encuesta');
      $this->load->model('Usuario');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_materias','gm');
      $this->load->model('Gestor_carreras','gc');
      $this->load->model('Gestor_encuestas','ge');
      $this->load->model('Gestor_departamentos','gd');
      $this->load->model('Gestor_usuarios','gu');

      //obtener la lista de docentes que participa en la encuesta, y el datos del usuario actual
      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $listaDocentes = $encuesta->listarDocentes($idMateria, $idCarrera);
      $usuario = $this->gu->dame($this->data['usuarioLogin']->id);
      $datosDocente = $usuario->dameDatosDocente($idMateria);
      //si el usuario no es docente o tiene acceso como jefe de cátedra
      if (!$this->ion_auth->in_group('docentes') || (count($datosDocente)>0 && $datosDocente['tipoAcceso']=='J')){
        //mostrar resultados para todos los docentes
        $docentes = &$listaDocentes;
      }
      else{
        //sino, mostrar solo resultado del usuario logueado
        $docentes = array();
        foreach ($listaDocentes as $docente) {
          if ($docente->id == $usuario->id){$docentes[0] = $docente;break;} 
        }
      }
      //obtener datos de la encuesta
      $formulario = $this->gf->dame($idFormulario);
      $carrera = $this->gc->dame($idCarrera);
      $materia = $this->gm->dame($idMateria);
      $departamento = $this->gd->dame($carrera->idDepartamento);
      $secciones = $formulario->listarSeccionesCarrera($idCarrera);
      $this->Usuario->id = 0; //importante. Es el id de un docente no existente.
      
      //recorrer secciones, docentes, y preguntas obteniendo resultados
      $datos_secciones = array();
      foreach ($secciones as $i => $seccion) {
        $datos_secciones[$i]['seccion'] = $seccion;
        $datos_secciones[$i]['subsecciones'] = array();
        $datos_secciones[$i]['indice'] = ($indicesSecciones)?$encuesta->indiceSeccionMateria($idMateria, $idCarrera, $seccion->idSeccion):null;
        switch ($seccion->tipo){
        //si la sección es referida a docentes
        case 'D':
          foreach ($docentes as $j => $docente) {
            $datos_secciones[$i]['subsecciones'][$j] = array(
              'docente' => $docente,
              'preguntas' => $this->_dameDatosSeccionMateria($seccion, $encuesta, $docente->id, $materia->idMateria, $carrera->idCarrera),
              'indice' => ($indicesDocentes)?$encuesta->indiceDocenteMateria($idMateria, $idCarrera, $seccion->idSeccion, $docente->id):null
            );
          }
          break;
        //si la sección es referida a la materia (sección comun)
        case 'N':
          $datos_secciones[$i]['subsecciones'][0] =  array(
            'docente' => $this->Usuario,
            'preguntas' => $this->_dameDatosSeccionMateria($seccion, $encuesta, 0, $materia->idMateria, $carrera->idCarrera),
            'indice' => null
          );
          break;
        }
      }
      //datos para enviar a la vista
      $datos = array(
        'encuesta' => &$encuesta,
        'formulario' => &$formulario,
        'carrera' => &$carrera,
        'departamento' => &$departamento,
        'materia' => &$materia,
        'claves' => $encuesta->cantidadClavesMateria($idMateria, $idCarrera),
        'indice' => ($indiceGlobal)?$encuesta->indiceGlobalMateria($idMateria, $idCarrera):null,
        'graficos' => &$graficos,
        'secciones' => &$datos_secciones
      );
      $this->load->view('informe_materia', $datos);
    }
    else{
      $this->load->view('solicitud_informe_materia', $this->data);
    }
  }

  /*
   * Solicitar y mostrar un informe por carrera
   * Última revisión: 2012-02-09 8:17 p.m.
   */
  public function carrera(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos','jefes_departamentos','directores'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idCarrera','Carrera','required|is_natural_no_zero');
    $this->form_validation->set_rules('idEncuesta','Encuesta','required|is_natural_no_zero');
    $this->form_validation->set_rules('idFormulario','Formulario','required|is_natural_no_zero');
    if($this->form_validation->run()){
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      $idCarrera = (int)$this->input->post('idCarrera');
      $indicesSecciones = (bool)$this->input->post('indicesSecciones');
      $indiceGlobal = (bool)$this->input->post('indiceGlobal');
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Carrera');
      $this->load->model('Formulario');
      $this->load->model('Departamento');
      $this->load->model('Encuesta');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_carreras','gc');
      $this->load->model('Gestor_departamentos','gd');
      $this->load->model('Gestor_encuestas','ge');
      //obtener datos de la encuesta
      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $formulario = $this->gf->dame($idFormulario);
      $carrera = $this->gc->dame($idCarrera);
      $departamento = $this->gd->dame($carrera->idDepartamento);
      $secciones = $formulario->listarSeccionesCarrera($idCarrera);

      $datos_secciones = array();
      foreach ($secciones as $i => $seccion) {
        $datos_secciones[$i]['seccion'] = $seccion;
        $datos_secciones[$i]['items'] = array();
        $datos_secciones[$i]['indice'] = ($indicesSecciones)?$encuesta->indiceSeccionCarrera($idCarrera, $seccion->idSeccion):null;        
        $items = $seccion->listarItemsCarrera($idCarrera);
        foreach ($items as $k => $item) {
          switch ($item->tipo) {
          case 'S': case 'M': case 'N':
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => $encuesta->respuestasPreguntaCarrera($item->idPregunta, $idCarrera)
            );
            break;
          default:
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => null
            );
            break;
          }
        }
      }
      //datos para enviar a la vista
      $datos = array(
        'encuesta' => &$encuesta,
        'formulario' => &$formulario,
        'carrera' => &$carrera,
        'departamento' => &$departamento,
        'claves' => $encuesta->cantidadClavesCarrera($idCarrera),
        'indice' => ($indiceGlobal)?$encuesta->indiceGlobalCarrera($idCarrera):null,
        'secciones' => &$datos_secciones
      );
      $this->load->view('informe_carrera', $datos);
    }
    else{
      $this->load->view('solicitud_informe_carrera', $this->data);
    }
  }

  /*
   * Solicitar y mostrar un informe por departamento
   * Última revisión: 2012-02-09 8:17 p.m.
   */
  public function departamento(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos','jefes_departamentos'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idDepartamento','Departamento','required|is_natural_no_zero');
    $this->form_validation->set_rules('idEncuesta','Encuesta','required|is_natural_no_zero');
    $this->form_validation->set_rules('idFormulario','Formulario','required|is_natural_no_zero');
    if($this->form_validation->run()){
      $idDepartamento = (int)$this->input->post('idDepartamento');
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Departamento');
      $this->load->model('Formulario');
      $this->load->model('Encuesta');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_departamentos','gd');
      $this->load->model('Gestor_encuestas','ge');
      //obtener datos de la encuesta
      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $formulario = $this->gf->dame($idFormulario);
      $departamento= $this->gd->dame($idDepartamento);
      $secciones = $formulario->listarSecciones();
      
      $datos_secciones = array();
      foreach ($secciones as $i => $seccion) {
        $datos_secciones[$i]['seccion'] = $seccion;
        $datos_secciones[$i]['items'] = array();
        $items = $seccion->listarItems();
        foreach ($items as $k => $item) {
          switch ($item->tipo) {
          case 'S': case 'M': case 'N':
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => $encuesta->respuestasPreguntaDepartamento($item->idPregunta, $idDepartamento)
            );
            break;
          default:
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => null
            );
            break;
          }
        }
      }
      //datos para enviar a la vista
      $datos = array(
        'encuesta' => &$encuesta,
        'formulario' => &$formulario,
        'departamento' => &$departamento,
        'claves' => $encuesta->cantidadClavesDepartamento($idDepartamento),
        'secciones' => &$datos_secciones
      );
      $this->load->view('informe_departamento', $datos);
    }
    else{
      $this->load->view('solicitud_informe_departamento', $this->data);
    }
  }

  /*
   * Solicitar y mostrar un informe por facultad
   * Última revisión: 2012-02-10 1:42 p.m.
   */
  public function facultad(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idEncuesta','Encuesta','required|is_natural_no_zero');
    $this->form_validation->set_rules('idFormulario','Formulario','required|is_natural_no_zero');
    if($this->form_validation->run()){
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Formulario');
      $this->load->model('Encuesta');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_encuestas','ge');
  
      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $formulario = $this->gf->dame($idFormulario);
      $secciones = $formulario->listarSecciones();
      
      $datos_secciones = array();
      foreach ($secciones as $i => $seccion) {
        $datos_secciones[$i]['seccion'] = $seccion;
        $datos_secciones[$i]['items'] = array();
        $items = $seccion->listarItems();
        foreach ($items as $k => $item) {
          switch ($item->tipo) {
          case 'S': case 'M': case 'N':
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => $encuesta->respuestasPreguntaFacultad($item->idPregunta)
            );
            break;
          default:
            $datos_secciones[$i]['items'][$k] = array(
              'item' => $item,
              'respuestas' => null
            );
            break;
          }
        }
      }
      //datos para enviar a la vista
      $datos = array(
        'encuesta' => &$encuesta,
        'formulario' => &$formulario,
        'secciones' => &$datos_secciones
      );
      $this->load->view('informe_facultad', $datos);
    }
    else{
      $this->load->view('solicitud_informe_facultad', $this->data);
    }
  }

  /*
   * Solicitar y mostrar un informe por clave (es decir, por alumno)
   * Última revisión: 2012-02-13 7:09 p.m.
   */
  public function clave(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos','jefes_departamentos','directores','docentes'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idClave','Clave','required|is_natural_no_zero');
    $this->form_validation->set_rules('idMateria','Materia','required|is_natural_no_zero');
    $this->form_validation->set_rules('idCarrera','Carrera','required|is_natural_no_zero');
    if($this->form_validation->run()){
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      $idClave = (int)$this->input->post('idClave');
      $idMateria = (int)$this->input->post('idMateria');
      $idCarrera = (int)$this->input->post('idCarrera');
      $indicesDocentes = (bool)$this->input->post('indicesDocentes');
      $indicesSecciones = (bool)$this->input->post('indicesSecciones');
      $indiceGlobal = (bool)$this->input->post('indiceGlobal');
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Usuario');
      $this->load->model('Materia');
      $this->load->model('Carrera');
      $this->load->model('Departamento');
      $this->load->model('Clave');
      $this->load->model('Formulario');
      $this->load->model('Encuesta');
      $this->load->model('Usuario');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_materias','gm');
      $this->load->model('Gestor_carreras','gc');
      $this->load->model('Gestor_encuestas','ge');
      $this->load->model('Gestor_departamentos','gd');
      $this->load->model('Gestor_usuarios','gu');

      //obtener la lista de docentes que participa en la encuesta, y el datos del usuario actual
      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $listaDocentes = $encuesta->listarDocentes($idMateria, $idCarrera);
      $usuario = $this->gu->dame($this->data['usuarioLogin']->id);
      $datosDocente = $usuario->dameDatosDocente($idMateria);
      //si el usuario no es docente o tiene acceso como jefe de cátedra
      if (!$this->ion_auth->in_group('docentes') || (count($datosDocente)>0 && $datosDocente['tipoAcceso']=='J')){
        //mostrar resultados para todos los docentes
        $docentes = &$listaDocentes;
      }
      else{
        //sino, mostrar solo resultado del usuario logueado
        $docentes = array();
        foreach ($listaDocentes as $docente) {
          if ($docente->id == $usuario->id){$docentes[0] = $docente;break;} 
        }
      }
      //obtener datos de la encuesta
      $formulario = $this->gf->dame($idFormulario);
      $carrera = $this->gc->dame($idCarrera);
      $materia = $this->gm->dame($idMateria);
      $departamento = $this->gd->dame($carrera->idDepartamento);
      $clave = $encuesta->dameClave($idClave, $idMateria, $idCarrera);
      $secciones = $formulario->listarSeccionesCarrera($idCarrera);
      $this->Usuario->id = 0; //importante. Es el id de un docente no existente.
      
      //recorrer secciones, docentes, y preguntas obteniendo resultados
      $datos_secciones = array();
      foreach ($secciones as $i => $seccion) {
        $datos_secciones[$i]['seccion'] = $seccion;
        $datos_secciones[$i]['subsecciones'] = array();
        $datos_secciones[$i]['indice'] = ($indicesSecciones)?$encuesta->indiceSeccionClave($idClave, $idMateria, $idCarrera, $seccion->idSeccion):null;
        switch ($seccion->tipo){
        //si la sección es referida a docentes
        case 'D':
          foreach ($docentes as $j => $docente) {
            $items = $seccion->listarItemsCarrera($idCarrera);
            $datos_items = array();
            foreach ($items as $k => $item) {
              $datos_items[$k] = array(
                'item' => $item,
                'respuestas' => $clave->respuestaPregunta($item->idPregunta, $docente->id)
              );
            }            
            $datos_secciones[$i]['subsecciones'][$j] = array(
              'docente' => $docente,
              'items' => $datos_items,
              'indice' => ($indicesDocentes)?$encuesta->indiceDocenteClave($idClave, $idMateria, $idCarrera, $seccion->idSeccion, $docente->id):null
            );
          }
          break;
        //si la sección es referida a la materia (sección comun)
        case 'N':
          $items = $seccion->listarItemsCarrera($idCarrera);
          $datos_items = array();
          foreach ($items as $k => $item) {
            $datos_items[$k] = array(
              'item' => $item,
              'respuestas' => $clave->respuestaPregunta($item->idPregunta, 0)
            );
          }
          $datos_secciones[$i]['subsecciones'][0] =  array(
            'docente' => $this->Usuario,
            'items' => $datos_items,
            'indice' => null
          );
          break;
        }
      }

      //datos para enviar a la vista
      $datos = array(
        'encuesta' => &$encuesta,
        'formulario' => &$formulario,
        'carrera' => &$carrera,
        'departamento' => &$departamento,
        'materia' => &$materia,
        'indice' => ($indiceGlobal)?$encuesta->indiceGlobalClave($idClave, $idMateria, $idCarrera):null,
        'clave' => &$clave,
        'secciones' => &$datos_secciones
      );
      $this->load->view('informe_clave', $datos);
    }
    else{
      $this->load->view('solicitud_informe_clave', $this->data);
    }
  }








  /*
   * Solicitar y mostrar un informe por facultad
   * Última revisión: 2012-02-10 1:42 p.m.
   */
  public function archivoMateria(){
    //verifico si el usuario tiene permisos para continuar
    if (!$this->ion_auth->logged_in()){
      redirect('usuarios/login');
    }
    elseif (!$this->ion_auth->in_group(array('admin','decanos','jefes_departamentos','directores','docentes'))){
      show_error('No tiene permisos para realizar esta operación.');
      return;
    }
    //verifico datos POST
    $this->form_validation->set_rules('idEncuesta','Encuesta','required|is_natural_no_zero');
    $this->form_validation->set_rules('idFormulario','Formulario','required|is_natural_no_zero');
    $this->form_validation->set_rules('idCarrera','Carrera','required|is_natural_no_zero');
    $this->form_validation->set_rules('idMateria','Materia','required|is_natural_no_zero');
    $this->form_validation->set_rules('tipo','Tipo de archivo','required|alpha');
    if($this->form_validation->run()){
      $idEncuesta = (int)$this->input->post('idEncuesta');
      $idFormulario = (int)$this->input->post('idFormulario');
      $idMateria = (int)$this->input->post('idMateria');
      $idCarrera = (int)$this->input->post('idCarrera');
      $tipo = $this->input->post('tipo');
      
      //cargo librerias y modelos
      $this->load->model('Opcion');
      $this->load->model('Usuario');
      $this->load->model('Pregunta');
      $this->load->model('Item');
      $this->load->model('Seccion');
      $this->load->model('Formulario');
      $this->load->model('Encuesta');
      $this->load->model('Gestor_formularios','gf');
      $this->load->model('Gestor_encuestas','ge');
      $this->load->library('PHPExcel/PHPExcel');

      $encuesta = $this->ge->dame($idEncuesta, $idFormulario);
      $formulario = $this->gf->dame($idFormulario);
      
      //Iniciar la planilla de cálculo
      $objPHPExcel = $this->phpexcel;
      $this->phpexcel->getProperties()->setCreator("Sistema Encuestas Vía Web")
                     ->setLastModifiedBy("Sistema Encuestas Vía Web")
                     ->setTitle("Datos Encuestas ".$encuesta->año.'/'.$encuesta->cuatrimestre)
                     ->setSubject("Datos Encuestas ".$encuesta->año.'/'.$encuesta->cuatrimestre)
                     ->setDescription("Datos de las encuestas para una materia.");
      $this->phpexcel->setActiveSheetIndex(0)
        ->setCellValueByColumnAndRow(0, 1, 'ID Pregunta')
        ->setCellValueByColumnAndRow(1, 1, 'Pregunta')
        ->setCellValueByColumnAndRow(2, 1, 'Opciones')
        ->setTitle('Preguntas');
      //obtengo datos de los docentes             
      $docentes = $encuesta->listarDocentes($idMateria, $idCarrera);
      $posDocente = array();
      foreach ($docentes as $i => $docente) {
        //genero un array para obtener posicion a partir del id
        $posDocente[$docente->id] = $i;
      }
      
      //recorrer secciones del formulario
      $posItem = array();
      $secItem = array();
      $cntPaginas = 1;
      $cntPreguntas = 0;
      $secciones = $formulario->listarSecciones();
      foreach ($secciones as $i => $seccion) {
        //activo la primer pagina de la planilla de cálculo
        $worksheet = $this->phpexcel->setActiveSheetIndex(0);
        
        //listo las preguntas de la sección
        $items = $seccion->listarItems();
        foreach ($items as $k => $item) {
          //genero un array para obtener posicion y seccion a partir del id
          $posItem[$item->idPregunta] = $k;
          $secItem[$item->idPregunta] = $i;
          
          //guardo datos de la pregunta en la primer pagina, a partir de la fila 2
          $opciones = $item->listarOpciones();
          $worksheet->setCellValueByColumnAndRow(0, 2+$cntPreguntas, $item->idPregunta)
                    ->setCellValueByColumnAndRow(1, 2+$cntPreguntas, $item->texto);
          foreach ($opciones as $j => $opcion) {
            $worksheet->setCellValueByColumnAndRow(2+$j, 2+$cntPreguntas, $opcion->texto.': '.$opcion->idOpcion);
          }
          $cntPreguntas++;
        }
        //creo una hoja de excel por cada seccion y por cada docente
        if ($seccion->tipo=='N'){
          //los datos van a partir de fila 4, clave nula
          $filaPagina[$cntPaginas] = 4; 
          $clavePagina[$cntPaginas] = null; 
          
          //crear hoja, colocarle titulo e identificadores de pregunta
          $this->phpexcel->createSheet();
          $worksheet = $this->phpexcel->setActiveSheetIndex($cntPaginas);
          $worksheet->setTitle('Sección '.($i+1)); //nombre de la hoja
          $worksheet->setCellValueByColumnAndRow(0, 1, $seccion->texto); //titulo de la hoja (celda A1)
          $worksheet->setCellValueByColumnAndRow(0, 3, 'Clave');
          foreach ($items as $k => $item) {
            //pongo el ID de la pregunta al inicio de cada columna correspondiente
            $worksheet->setCellValueByColumnAndRow($k+1, 3, 'Preg.'.$item->idPregunta);
          }
          $cntPaginas++; 
        }
        else{
          for($j=0; $j < count($docentes); $j++){
            //los datos van a partir de fila 4, clave nula
            $filaPagina[$cntPaginas] = 4;
            $clavePagina[$cntPaginas] = '';
            
            //crear hoja, colocarle titulo e identificadores de pregunta
            $this->phpexcel->createSheet();
            $worksheet = $this->phpexcel->setActiveSheetIndex($cntPaginas);
            $worksheet->setTitle('Sección '.($i+1).' Docente '.($j+1));
            $worksheet->setCellValueByColumnAndRow(0, 1, $seccion->texto.' - '.$docentes[$j]->nombre.' '.$docentes[$j]->apellido); //titulo de la hoja (celda A1)
            $worksheet->setCellValueByColumnAndRow(0, 3, 'Clave');
            foreach ($items as $k => $item) {
              //pongo el ID de la pregunta al inicio de cada columna correspondiente
              $worksheet->setCellValueByColumnAndRow($k+1, 3, $item->idPregunta);
            }
            $cntPaginas++;
          }
        }
      }
  
      //obtengo respuestas de la base de datos
      $respuestas = $encuesta->respuestasMateria($idCarrera, $idMateria);
      foreach ($respuestas as $respuesta) {
        $idPregunta = $respuesta['idPregunta'];
        $idDocente = $respuesta['idDocente'];
        
        //calculo la posicion de la columna y la pagina de la respuesta actual
        $col = $posItem[$idPregunta] + 1;
        $pagina =  $secItem[$idPregunta] + (($idDocente)?$posDocente[$idDocente]:0) + 1;
        
        //ubico la respuesta actual dentro de la planilla de cálculo. 
        //Es una hoja por sección y por docente. Cada columna tiene las respuestas a una pregunta.
        $this->phpexcel->setActiveSheetIndex($pagina)
          ->setCellValueByColumnAndRow(0,    $filaPagina[$pagina], $respuesta['idClave'])
          ->setCellValueByColumnAndRow($col, $filaPagina[$pagina], $respuesta['opcion'].$respuesta['texto']);
        
        //si la clave cambia, significa que debo pasar a la siguiente fila de la pagina actual (empiezan respuestas de otro alumno)
        if ($respuesta['idClave'] != $clavePagina[$pagina]){
          if ($clavePagina[$pagina] != null) $filaPagina[$pagina]++;
          $clavePagina[$pagina] = $respuesta['idClave']; 
        }
      }
  
      //genero la descarga para el usuario
      switch ($tipo) {
        case 'xls':
          ob_end_clean();
          header('Content-Type: application/vnd.ms-excel');
          header('Content-Disposition: attachment;filename="myfile.xls"');
          header('Cache-Control: max-age=0');
          //genero el archivo y guardo
          $objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, 'Excel5');
          $objWriter->save('php://output');
          ob_end_clean();
          break;
        case 'xlsx':
          ob_end_clean();
          header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
          header('Content-Disposition: attachment;filename="myfile.xlsx"');
          header('Cache-Control: max-age=0');
          $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
          $objWriter->save('php://output');
          ob_end_clean();
          break;
        default:
          show_404();
      }
    }
    //si no pasa la validación
    else{
      
    }
  }
}
?>
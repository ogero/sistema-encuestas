<?php

/**
 * 
 */
class Encuesta extends CI_Model{
	var $idEncuesta;
  var $idFormulario;
  var $año;
  var $cuatrimestre;
  var $fechaInicio;
  var $fechaFin;
  
  function __construct(){
    parent::__construct();
  }
  
  
  /**
   * Buscar una clave (usada o no). Devuleve un objeto en caso de éxito, o FALSE en caso de error.
   *
   * @access public
   * @param clave alfanumérica
   * @return object
   */
  public function buscarClave($clave){
    $clave = $this->db->escape($clave);
    $query = $this->db->query("call esp_buscar_clave($clave)");
    $data = $query->result('Clave');
    $query->free_result();
    //$this->db->reconnect();
    return ($data != FALSE)?$data[0]:FALSE;
  }


  /**
   * Obtener el listado de claves de acceso de la encuesta para una materia y carrera dada. Devuleve un array de objetos.
   *
   * @access  public
   * @param identificador de materia
   * @param identificador de carrera
   * @param posicion del primer item de la lista a mostrar
   * @param cantidad de items a mostrar (tamaño de página)
   * @return  array
   */  
  public function listarClavesMateria($idMateria, $idCarrera, $pagNumero, $pagLongitud){
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $pagNumero = $this->db->escape($pagNumero);
    $pagLongitud = $this->db->escape($pagLongitud);
    $query = $this->db->query("call esp_listar_claves_encuesta_materia($idMateria, $idCarrera, $idEncuesta, $idFormulario, $pagNumero, $pagLongitud)");
    $data = $query->result('Clave');
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  /**
   * Cerrar o finalizar una encuesta. Devuleve 'ok' en caso de éxito o un mensaje en caso de error.
   *
   * @access public
   * @return string
   */
  public function finalizar(){
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_finalizar_encuesta($idEncuesta, $idFormulario)");
    $data = $query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->mensaje:'No se pudo conectar con la base de datos.';
  }
  
  /**
   * Obtiene cuantas claves se generaron y cuantas se usaron de una encuesta para una materia y una carrera.
   *
   * @access public
   * @param identificador de materia
   * @param idenificador de carrera
   * @return array
   */  
  public function cantidadClavesMateria($idMateria, $idCarrera){
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_cantidad_claves_materia($idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->row_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data; //devuelve dos elementos: Generadas y Utilizadas
  }
  
  /**
   * Obtiene cuantas claves se generaron y cuantas se usaron de una encuesta para una carrera.
   *
   * @access public
   * @param idenificador de carrera
   * @return array
   */  
  public function cantidadClavesCarrera($idCarrera){
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_cantidad_claves_carrera($idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->row_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data; //devuelve dos elementos: Generadas y Utilizadas
  }
  
  /**
   * Obtiene cuantas claves se generaron y cuantas se usaron de una encuesta para un departamento.
   *
   * @access public
   * @param idenificador de departamento
   * @return array
   */  
  public function cantidadClavesDepartamento($idDepartamento){
    $idDepartamento = $this->db->escape($idDepartamento);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_cantidad_claves_departamento($idDepartamento, $idEncuesta, $idFormulario)");
    $data=$query->row_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data; //devuelve dos elementos: Generadas y Utilizadas
  }
  
  /**
   * Obtiene las respuestas a una pregunta para una encuesta. Devuelve un array.
   *
   * @access public
   * @param identificador de la pregunta
   * @return array
   */  
  public function respuestasPreguntaFacultad($idPregunta){
    $idPregunta = $this->db->escape($idPregunta);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_respuestas_pregunta_facultad($idPregunta, $idEncuesta, $idFormulario)");
    $data=$query->result_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  /**
   * Obtiene las respuestas a una pregunta para un departamento y encuesta. Devuelve un array.
   *
   * @access public
   * @param identificador de la pregunta
   * @param idenificador de departamento
   * @return array
   */  
  public function respuestasPreguntaDepartamento($idPregunta, $idDepartamento){
    $idPregunta = $this->db->escape($idPregunta);
    $idDepartamento = $this->db->escape($idDepartamento);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_respuestas_pregunta_departamento($idPregunta, $idDepartamento, $idEncuesta, $idFormulario)");
    $data=$query->result_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  /**
   * Obtiene las respuestas a una pregunta para una carrera y encuesta. Devuelve un array.
   *
   * @access public
   * @param identificador de la pregunta
   * @param idenificador de carrera
   * @return array
   */  
  public function respuestasPreguntaCarrera($idPregunta, $idCarrera){
    $idPregunta = $this->db->escape($idPregunta);
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_respuestas_pregunta_carrera($idPregunta, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->result_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  /**
   * Obtiene las respuestas a una pregunta para una materia, carrera y encuesta. Devuelve un array.
   *
   * @access public
   * @param identificador de la pregunta
   * @param identificador de docente. En caso de no referirse a un docente, este parámetro debe ser 0 o null.
   * @param identificador de materia
   * @param idenificador de carrera
   * @return array
   */  
  public function respuestasPreguntaMateria($idPregunta, $idDocente, $idMateria, $idCarrera){
    $idPregunta = $this->db->escape($idPregunta);
    $idDocente = $this->db->escape($idDocente);
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_respuestas_pregunta_materia($idPregunta, $idDocente, $idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->result_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  
  /**
   * Obtiene las respuestas del tipo texo de una pregunta para una materia, carrera y encuesta. Devuelve un array.
   *
   * @access public
   * @param identificador de la pregunta
   * @param identificador de materia
   * @param idenificador de carrera
   * @return array
   */  
  public function textosPreguntaMateria($idPregunta, $idMateria, $idCarrera){
    $idPregunta = $this->db->escape($idPregunta);
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_textos_pregunta_materia($idPregunta, $idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->result_array();
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  
    
  /**
   * Obtener el listado de docentes de las que hace referencia la encuesta. Devuleve un array de objetos.
   *
   * @access public
   * @param identificador de la materia
   * @param identificador de la carrera
   * @return array
   */
  public function listarDocentes($idMateria, $idCarrera){
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_listar_docentes_encuesta($idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data = $query->result('Usuario');
    $query->free_result();
    //$this->db->reconnect();
    return $data;
  }
  
  
  /**
   * Da de Alta una clave. Devuleve '=###' en caso de éxito donde ### es la clave generada, o un mensaje en caso de error.
   *
   * @access  public
   * @param identificador de la materia
   * @param identificador de la carrera
   * @param tipo de clave a generar
   * @return  string
   */
  public function altaClave($idMateria, $idCarrera, $tipo){
    $idMateria = $this->db->escape($idMateria);
    $idCarrera = $this->db->escape($idCarrera);
    $tipo = $this->db->escape($tipo);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_alta_clave($idMateria, $idCarrera, $idEncuesta, $idFormulario, $tipo)");
    $data = $query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->mensaje:'No se pudo conectar con la base de datos.';
  }
  
  
  
  
  
  
  
  
  
  /**
   * Obtiene el indice de una seccion de docentes para una encuesta de un alumno.
   *
   * @access public
   * @param idenificador de clave de acceso
   * @param identificador de materia
   * @param identificador de carrera
   * @param identificador de seccion
   * @param identificador de docente
   * @return array
   */  
  public function indiceDocenteClave($idClave, $idMateria, $idCarrera, $idSeccion, $idDocente){
    $idClave = $this->db->escape($idClave);
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idSeccion = $this->db->escape($idSeccion);
    $idDocente = $this->db->escape($idDocente);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_docente_clave($idClave, $idMateria, $idCarrera, $idEncuesta, $idFormulario, $idSeccion, $idDocente)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice de una seccion de docentes para una encuesta.
   *
   * @access public
   * @param identificador de materia
   * @param identificador de carrera
   * @param identificador de seccion
   * @param identificador de docente
   * @return array
   */  
  public function indiceDocenteMateria($idMateria, $idCarrera, $idSeccion, $idDocente){
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idSeccion = $this->db->escape($idSeccion);
    $idDocente = $this->db->escape($idDocente);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_docente_materia($idMateria, $idCarrera, $idEncuesta, $idFormulario, $idSeccion, $idDocente)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice de una seccion para una encuesta de un alumno.
   *
   * @access public
   * @param idenificador de clave de acceso
   * @param identificador de materia
   * @param identificador de carrera
   * @param identificador de seccion
   * @return array
   */  
  public function indiceSeccionClave($idClave, $idMateria, $idCarrera, $idSeccion){
    $idClave = $this->db->escape($idClave);
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idSeccion = $this->db->escape($idSeccion);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_seccion_clave($idClave, $idMateria, $idCarrera, $idEncuesta, $idFormulario, $idSeccion)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }

  /**
   * Obtiene el indice de una seccion para una encuesta de un alumno.
   *
   * @access public
   * @param identificador de materia
   * @param identificador de carrera
   * @param identificador de seccion
   * @return array
   */  
  public function indiceSeccionMateria($idMateria, $idCarrera, $idSeccion){
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idSeccion = $this->db->escape($idSeccion);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_seccion_materia($idMateria, $idCarrera, $idEncuesta, $idFormulario, $idSeccion)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice de una seccion de docentes para una encuesta.
   *
   * @access public
   * @param identificador de carrera
   * @param identificador de seccion
   * @return array
   */  
  public function indiceSeccionCarrera($idCarrera, $idSeccion){
    $idCarrera = $this->db->escape($idCarrera);
    $idSeccion = $this->db->escape($idSeccion);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_seccion_carrera($idCarrera, $idEncuesta, $idFormulario, $idSeccion)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice global para una encuesta de un alumno.
   *
   * @access public
   * @param idenificador de clave de acceso
   * @param identificador de materia
   * @param identificador de carrera
   * @return array
   */  
  public function indiceGlobalClave($idClave, $idMateria, $idCarrera){
    $idClave = $this->db->escape($idClave);
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_global_clave($idClave, $idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice global de una materia para una encuesta.
   *
   * @access public
   * @param identificador de materia
   * @param identificador de carrera
   * @return array
   */  
  public function indiceGlobalMateria($idMateria, $idCarrera){
    $idCarrera = $this->db->escape($idCarrera);
    $idMateria = $this->db->escape($idMateria);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_global_materia($idMateria, $idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  /**
   * Obtiene el indice global de una carrera
   *
   * @access public
   * @param identificador de carrera
   * @return array
   */  
  public function indiceGlobalCarrera($idCarrera){
    $idCarrera = $this->db->escape($idCarrera);
    $idEncuesta = $this->db->escape($this->idEncuesta);
    $idFormulario = $this->db->escape($this->idFormulario);
    $query = $this->db->query("call esp_indice_global_carrera($idCarrera, $idEncuesta, $idFormulario)");
    $data=$query->row();
    $query->free_result();
    //$this->db->reconnect();
    return ($data)?$data->indice:0;
  }
  
  
}

?>
<?php

/**
 * 
 */
class Seccion extends CI_Model{
  var $idSeccion;
  var $idFormulario;
  var $idCarrera;
  var $texto;
  var $descripcion;
  var $tipo;
  
  function __construct(){
    parent::__construct();
  }
  
  /**
   * Obtener el listado de items que pertenecen al formulario (las que son comunes a todas las carreras). Devuleve un array de objetos.
   *
   * @access public
   * @return arrayItems
   */
  public function listarItems(){
    $idFormulario = $this->db->escape($this->idFormulario);
    $idSeccion = $this->db->escape($this->idSeccion);
    $query = $this->db->query("call esp_listar_items_seccion($idSeccion, $idFormulario)");
    $data = $query->result('Item');
    $query->free_result();
    return $data;
  }
  
  /**
   * Obtener el listado de items que pertenecen al formulario (las comunes y las de una carrera). Devuleve un array de objetos.
   *
   * @access public
   * @return arrayItems
   */
  public function listarItemsCarrera($idCarrera){
    $idFormulario = $this->db->escape($this->idFormulario);
    $idSeccion = $this->db->escape($this->idSeccion);
    $idCarrera = $this->db->escape($idCarrera);
    $query = $this->db->query("call esp_listar_items_seccion_carrera($idSeccion, $idFormulario, $idCarrera)");
    $data = $query->result('Item');
    $query->free_result();
    return $data;
  }
  
  /**
   * Da de Alta un nuevo item. Devuleve el id en caso de éxito o un mensaje en caso de error.
   *
   * @access  public
   * @param identificador de la pregunta que corresponde al item
   * @param identificador de la carrera que crea el item (si es nulo es comunn a todas las carreras)
   * @param posicion del item dentro de la seccion
   * @param cantidad de preguntas que las carreras pueden agregar al formulario
   * @return  string
   */
  public function altaItem($idPregunta, $idCarrera, $posicion){
    $idPregunta = $this->db->escape($idPregunta);
    $idCarrera = $this->db->escape($idCarrera);
    $posicion = $this->db->escape($posicion);
    $idFormulario = $this->db->escape($this->idFormulario);
    $idSeccion = $this->db->escape($this->idSeccion);
    $query = $this->db->query("call esp_alta_item($idSeccion, $idFormulario, $idPregunta, $idCarrera, $posicion)");
    $data = $query->row();
    $query->free_result();
    return ($data)?$data->mensaje:'No se pudo conectar con la base de datos.';
  }
  
  /*
   * 
   */
  public function bajaItem($idPregunta){
    $idFormulario = $this->db->escape($this->idFormulario);
    $idSeccion = $this->db->escape($this->idSeccion);
    $idPregunta = $this->db->escape($idPregunta);
    $query = $this->db->query("call esp_baja_item($idSeccion, $idFormulario, $idPregunta)");
    $data = $query->row();
    $query->free_result();
    return ($data)?$data->mensaje:'No se pudo conectar con la base de datos.';
  }
  
  /*
   * 
   */
  public function bajaItemCarrera($idPregunta, $idCarrera){
    $idFormulario = $this->db->escape($this->idFormulario);
    $idSeccion = $this->db->escape($this->idSeccion);
    $idPregunta = $this->db->escape($idPregunta);
    $idCarrera = $this->db->escape($idCarrera);
    $query = $this->db->query("call esp_baja_item_carrera($idSeccion, $idFormulario, $idPregunta, $idCarrera)");
    $data = $query->row();
    $query->free_result();
    return ($data)?$data->mensaje:'No se pudo conectar con la base de datos.';
  }
  
}

?>
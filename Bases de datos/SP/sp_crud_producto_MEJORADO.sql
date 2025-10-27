DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_crud_producto`$$

CREATE DEFINER=`distrnnf`@`localhost` PROCEDURE `sp_crud_producto` (
    IN `p_accion` INT, 
    IN `p_id` INT, 
    IN `p_nombre` VARCHAR(255), 
    IN `p_descripcion` VARCHAR(255), 
    IN `p_isv_id` INT, 
    IN `p_precio_base` DOUBLE, 
    IN `p_ultimo_costo_compra` DOUBLE, 
    IN `p_costo_promedio` DOUBLE, 
    IN `p_codigo_barra` VARCHAR(100), 
    IN `p_codigo_estatal` VARCHAR(255), 
    IN `p_estado_id` INT, 
    IN `p_subcategoria_id` INT, 
    IN `p_marca_id` INT, 
    IN `p_unidad_medida_venta_id` INT, 
    IN `p_precio1` DECIMAL(16,2), 
    IN `p_precio2` DECIMAL(16,2), 
    IN `p_precio3` DECIMAL(16,2), 
    IN `p_precio4` DECIMAL(16,2), 
    IN `p_users_id` BIGINT, 
    IN `p_descuento_unitario` DECIMAL(16,2), 
    IN `p_descuento_tercera` TINYINT, 
    IN `p_descuento_cuarta` TINYINT, 
    IN `p_imagen` LONGBLOB
)
BEGIN
    -- Variables locales
    DECLARE v_desc_tercera TINYINT(1);
    DECLARE v_desc_cuarta  TINYINT(1);
    DECLARE v_producto_id  INT;

    -- Normalizar banderas a 0/1
    SET v_desc_tercera = IFNULL(p_descuento_tercera, 0);
    SET v_desc_cuarta  = IFNULL(p_descuento_cuarta , 0);

    SET v_desc_tercera = IF(v_desc_tercera IN (0,1), v_desc_tercera, 0);
    SET v_desc_cuarta  = IF(v_desc_cuarta  IN (0,1), v_desc_cuarta , 0);

    -- ====================
    -- ACCIÓN 1: INSERTAR
    -- ====================
    IF p_accion = 1 THEN
        INSERT INTO producto (
            nombre,
            isv_id,
            descripcion,
            descuento_unitario,
            descuento_tercera,
            descuento_cuarta,
            precio_base,
            ultimo_costo_compra,
            costo_promedio,
            codigo_barra,
            codigo_estatal,
            estado_id,
            subcategoria_id,
            marca_id,
            unidad_medida_venta_id,
            precio1, 
            precio2, 
            precio3, 
            precio4,
            imagen,
            users_id, 
            created_at, 
            updated_at
        ) VALUES (
            p_nombre,
            p_isv_id,
            p_descripcion,
            p_descuento_unitario,
            v_desc_tercera,
            v_desc_cuarta,
            p_precio_base,
            p_ultimo_costo_compra,
            p_costo_promedio,
            p_codigo_barra,
            p_codigo_estatal,
            p_estado_id,
            p_subcategoria_id,
            p_marca_id,
            p_unidad_medida_venta_id,
            p_precio1, 
            p_precio2, 
            p_precio3, 
            p_precio4,
            p_imagen,
            p_users_id, 
            NOW(), 
            NOW()
        );

        -- CRÍTICO: Retornar el ID del producto insertado
        SET v_producto_id = LAST_INSERT_ID();
        SELECT v_producto_id as id, 'Producto creado exitosamente' as mensaje;

    -- ====================
    -- ACCIÓN 2: ACTUALIZAR
    -- ====================
    ELSEIF p_accion = 2 THEN
        UPDATE producto
        SET
            nombre                   = p_nombre,
            isv_id                   = p_isv_id,
            descripcion              = p_descripcion,
            descuento_unitario       = p_descuento_unitario,
            descuento_tercera        = v_desc_tercera,
            descuento_cuarta         = v_desc_cuarta,
            precio_base              = p_precio_base,
            ultimo_costo_compra      = p_ultimo_costo_compra,
            costo_promedio           = p_costo_promedio,
            codigo_barra             = p_codigo_barra,
            codigo_estatal           = p_codigo_estatal,
            estado_id                = p_estado_id,
            subcategoria_id          = p_subcategoria_id,
            marca_id                 = p_marca_id,
            unidad_medida_venta_id   = p_unidad_medida_venta_id,
            precio1                  = p_precio1,
            precio2                  = p_precio2,
            precio3                  = p_precio3,
            precio4                  = p_precio4,
            imagen                   = p_imagen,
            users_id                 = p_users_id,
            updated_at               = NOW()
        WHERE id = p_id;

        -- Retornar confirmación
        SELECT p_id as id, 'Producto actualizado exitosamente' as mensaje;

    -- ====================
    -- ACCIÓN 3: ELIMINAR (Borrado lógico)
    -- ====================
    ELSEIF p_accion = 3 THEN
        UPDATE producto 
        SET estado_id = 2,
            updated_at = NOW()
        WHERE id = p_id;

        -- Retornar confirmación
        SELECT p_id as id, 'Producto eliminado exitosamente' as mensaje;

    -- ====================
    -- ACCIÓN 4: CONSULTAR DETALLADO
    -- ====================
    ELSEIF p_accion = 4 THEN
        SELECT 
            p.id                            AS 'id',
            p.nombre                        AS 'Nombre',
            p.descripcion                   AS 'Descripcion',
            i.cantidad                      AS 'ISV',
            p.precio_base                   AS 'PrecioBase',
            p.codigo_barra                  AS 'Codigo de Barras',
            p.codigo_estatal                AS 'Codigo Estatal',
            p.descuento_unitario            AS 'Descuento Unitario',
            p.descuento_tercera             AS 'Aplica Tercera Edad',
            p.descuento_cuarta              AS 'Aplica Cuarta Edad',
            m.nombre                        AS 'Marca',
            c.nombre                        AS 'Categoria',
            sc.nombre                       AS 'Subcategoria',
            uc.unidad                       AS 'Unidad',
            uc.nombre                       AS 'Presentacion',
            p.precio1, 
            p.precio2, 
            p.precio3, 
            p.precio4,
            p.imagen,
            p.ultimo_costo_compra,
            p.costo_promedio,
            p.estado_id,
            p.created_at,
            p.updated_at
        FROM producto p
        INNER JOIN subcategoria sc   ON sc.id = p.subcategoria_id
        INNER JOIN categoria c       ON c.id = sc.categoria_id
        INNER JOIN marca m           ON m.id = p.marca_id
        INNER JOIN unidad_medida uc  ON uc.id = p.unidad_medida_venta_id
        INNER JOIN isv i             ON i.id = p.isv_id
        WHERE p.id = p_id;

    -- ====================
    -- ACCIÓN 5: LISTAR TODOS (Opcional - útil para reportes)
    -- ====================
    ELSEIF p_accion = 5 THEN
        SELECT 
            p.id,
            p.nombre,
            p.codigo_barra,
            p.precio_base,
            m.nombre as marca,
            sc.nombre as subcategoria,
            uc.unidad as unidad,
            p.estado_id,
            CASE 
                WHEN p.estado_id = 1 THEN 'Activo'
                WHEN p.estado_id = 2 THEN 'Inactivo'
                ELSE 'Desconocido'
            END as estado_nombre
        FROM producto p
        INNER JOIN subcategoria sc   ON sc.id = p.subcategoria_id
        INNER JOIN marca m           ON m.id = p.marca_id
        INNER JOIN unidad_medida uc  ON uc.id = p.unidad_medida_venta_id
        WHERE p.estado_id = 1
        ORDER BY p.nombre ASC;

    ELSE
        -- Acción no válida
        SELECT 0 as id, 'Acción no válida' as mensaje, p_accion as accion_recibida;
    END IF;

END$$

DELIMITER ;

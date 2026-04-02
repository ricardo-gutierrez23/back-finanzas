<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
-- 1. TABLA DE USUARIOS (Con roles)
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    username VARCHAR(100) UNIQUE,
    email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Usuario',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABLA DE MÉTODOS DE PAGO
CREATE TABLE IF NOT EXISTS payment_methods (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABLA DE CATEGORÍAS
CREATE TABLE IF NOT EXISTS categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('Ingreso', 'Gasto')),
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABLA DE TRANSACCIONES (MOVIMIENTOS)
CREATE TABLE IF NOT EXISTS transactions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    payment_method_id BIGINT NOT NULL,
    amount NUMERIC(10, 2) NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT,
    observations TEXT,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_transaction_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaction_payment FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE RESTRICT
);

-- Indices
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(transaction_date);

-- FUNCIONES
CREATE OR REPLACE FUNCTION fn_check_amount_positive()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.amount <= 0 THEN
        RAISE EXCEPTION 'El monto de la transacción (%) debe ser mayor a 0.', NEW.amount;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_check_positive_amount ON transactions;
CREATE TRIGGER trg_check_positive_amount
BEFORE INSERT OR UPDATE ON transactions
FOR EACH ROW
EXECUTE FUNCTION fn_check_amount_positive();

CREATE OR REPLACE FUNCTION fn_get_user_summary(p_user_id BIGINT, p_month INT, p_year INT)
RETURNS JSON AS $$
DECLARE
    v_total_income NUMERIC(10,2) := 0;
    v_total_expense NUMERIC(10,2) := 0;
    v_current_balance NUMERIC(10,2) := 0;
    v_categories JSON;
    v_income_categories JSON;
BEGIN
    SELECT COALESCE(SUM(t.amount), 0) INTO v_total_income
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = p_user_id AND c.type = 'Ingreso' 
    AND EXTRACT(MONTH FROM t.transaction_date)::INTEGER = p_month 
    AND EXTRACT(YEAR FROM t.transaction_date)::INTEGER = p_year;

    SELECT COALESCE(SUM(t.amount), 0) INTO v_total_expense
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = p_user_id AND c.type = 'Gasto' 
    AND EXTRACT(MONTH FROM t.transaction_date)::INTEGER = p_month 
    AND EXTRACT(YEAR FROM t.transaction_date)::INTEGER = p_year;
      
    v_current_balance := v_total_income - v_total_expense;

    SELECT json_agg(t) INTO v_categories FROM (
        SELECT c.name as category, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = p_user_id 
          AND c.type = 'Gasto'
          AND EXTRACT(MONTH FROM t.transaction_date)::INTEGER = p_month 
          AND EXTRACT(YEAR FROM t.transaction_date)::INTEGER = p_year
        GROUP BY c.name
    ) t;

    SELECT json_agg(t) INTO v_income_categories FROM (
        SELECT c.name as category, SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = p_user_id 
          AND c.type = 'Ingreso'
          AND EXTRACT(MONTH FROM t.transaction_date)::INTEGER = p_month 
          AND EXTRACT(YEAR FROM t.transaction_date)::INTEGER = p_year
        GROUP BY c.name
    ) t;

    RETURN json_build_object(
        'total_income', v_total_income,
        'total_expense', v_total_expense,
        'current_balance', v_current_balance,
        'expenses_by_category', COALESCE(v_categories, '[]'::json),
        'income_by_category', COALESCE(v_income_categories, '[]'::json)
    );
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_create_category(p_name VARCHAR, p_type VARCHAR)
RETURNS JSON AS $$
DECLARE v_id BIGINT;
BEGIN
    INSERT INTO categories (name, type) VALUES (p_name, p_type) RETURNING id INTO v_id;
    RETURN json_build_object('status', 'success', 'id', v_id, 'message', 'Categoría creada');
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('status', 'error', 'message', SQLERRM);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_create_transaction(p_user_id BIGINT, p_cat_id BIGINT, p_pay_id BIGINT, p_amount NUMERIC, p_date DATE, p_desc TEXT, p_obs TEXT)
RETURNS JSON AS $$
DECLARE v_id BIGINT;
BEGIN
    INSERT INTO transactions (user_id, category_id, payment_method_id, amount, transaction_date, description, observations)
    VALUES (p_user_id, p_cat_id, p_pay_id, p_amount, p_date, p_desc, p_obs)
    RETURNING id INTO v_id;
    RETURN json_build_object('status', 'success', 'id', v_id, 'message', 'Registro exitoso');
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('status', 'error', 'message', SQLERRM);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_update_transaction(p_id BIGINT, p_user_id BIGINT, p_cat_id BIGINT, p_pay_id BIGINT, p_amount NUMERIC, p_date DATE, p_desc TEXT, p_obs TEXT)
RETURNS JSON AS $$
BEGIN
    UPDATE transactions SET category_id = p_cat_id, payment_method_id = p_pay_id, amount = p_amount, transaction_date = p_date, description = p_desc, observations = p_obs, updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id AND user_id = p_user_id;
    IF FOUND THEN RETURN json_build_object('status', 'success', 'message', 'Actualizado');
    ELSE RETURN json_build_object('status', 'error', 'message', 'Error'); END IF;
EXCEPTION WHEN OTHERS THEN RETURN json_build_object('status', 'error', 'message', SQLERRM);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_delete_transaction(p_id BIGINT, p_user_id BIGINT)
RETURNS JSON AS $$
BEGIN
    DELETE FROM transactions WHERE id = p_id AND user_id = p_user_id;
    IF FOUND THEN RETURN json_build_object('status', 'success', 'message', 'Eliminado');
    ELSE RETURN json_build_object('status', 'error', 'message', 'Error'); END IF;
EXCEPTION WHEN OTHERS THEN RETURN json_build_object('status', 'error', 'message', SQLERRM);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_get_categories()
RETURNS JSON AS $$
DECLARE v_result JSON;
BEGIN
    SELECT COALESCE(json_agg(row_to_json(c)), '[]') INTO v_result FROM categories c;
    RETURN v_result;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_get_payment_methods()
RETURNS JSON AS $$
DECLARE v_result JSON;
BEGIN
    SELECT COALESCE(json_agg(row_to_json(p)), '[]') INTO v_result FROM payment_methods p;
    RETURN v_result;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_get_transactions(p_user_id BIGINT, p_month INT, p_year INT)
RETURNS JSON AS $$
DECLARE v_result JSON;
BEGIN
    SELECT COALESCE(json_agg(json_build_object('id', t.id, 'amount', t.amount, 'transaction_date', t.transaction_date, 'description', t.description, 'category_name', c.name, 'category_type', c.type, 'payment_method_name', pm.name) ORDER BY t.transaction_date DESC), '[]') INTO v_result
    FROM transactions t JOIN categories c ON t.category_id = c.id JOIN payment_methods pm ON t.payment_method_id = pm.id
    WHERE t.user_id = p_user_id AND EXTRACT(MONTH FROM t.transaction_date) = p_month AND EXTRACT(YEAR FROM t.transaction_date) = p_year;
    RETURN v_result;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_get_yearly_trend(p_user_id BIGINT, p_year INT)
RETURNS JSON AS $$
DECLARE v_result JSON;
BEGIN
    SELECT COALESCE(json_agg(json_build_object('month', m.month_num, 'total', COALESCE(tx.total, 0)) ORDER BY m.month_num), '[]') INTO v_result
    FROM (SELECT generate_series(1, 12) AS month_num) m LEFT JOIN (SELECT EXTRACT(MONTH FROM t.transaction_date) AS month_num, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = p_user_id AND c.type = 'Gasto' AND EXTRACT(YEAR FROM t.transaction_date) = p_year GROUP BY EXTRACT(MONTH FROM t.transaction_date)) tx ON m.month_num = tx.month_num;
    RETURN v_result;
END;
$$ LANGUAGE plpgsql;

-- Datos iniciales
INSERT INTO payment_methods (name) VALUES ('Efectivo'), ('Tarjeta'), ('Transferencia'), ('Yape/Plin'), ('Otro') ON CONFLICT DO NOTHING;
INSERT INTO categories (name, type) VALUES ('Comida', 'Gasto'), ('Servicios', 'Gasto'), ('Transporte', 'Gasto'), ('Vivienda', 'Gasto'), ('Ingresos_Varios', 'Ingreso') ON CONFLICT DO NOTHING;
        ";
        \Illuminate\Support\Facades\DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

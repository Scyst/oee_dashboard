IF OBJECT_ID('dbo.LINE_SCHEDULES', 'U') IS NULL
BEGIN
    PRINT 'Creating table dbo.LINE_SCHEDULES...';
    CREATE TABLE dbo.LINE_SCHEDULES (
        id INT IDENTITY(1,1) PRIMARY KEY,
        line VARCHAR(50) NOT NULL,
        shift_name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        planned_break_minutes INT NOT NULL DEFAULT 0,
        is_active BIT NOT NULL DEFAULT 1
    );
    CREATE UNIQUE INDEX UQ_line_shift ON dbo.LINE_SCHEDULES (line, shift_name);
    PRINT 'Table dbo.LINE_SCHEDULES created successfully.';
END
ELSE
BEGIN
    PRINT 'Table dbo.LINE_SCHEDULES already exists.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Parts_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARTS'))
BEGIN
    PRINT 'Creating index IX_Parts_HealthCheck on dbo.IOT_TOOLBOX_PARTS...';
    CREATE NONCLUSTERED INDEX IX_Parts_HealthCheck
    ON dbo.IOT_TOOLBOX_PARTS (line, model, part_no);
    PRINT 'Index IX_Parts_HealthCheck created successfully.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parts_HealthCheck already exists.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Parameter_HealthCheck' AND object_id = OBJECT_ID('dbo.IOT_TOOLBOX_PARAMETER'))
BEGIN
    PRINT 'Creating index IX_Parameter_HealthCheck on dbo.IOT_TOOLBOX_PARAMETER...';
    CREATE NONCLUSTERED INDEX IX_Parameter_HealthCheck
    ON dbo.IOT_TOOLBOX_PARAMETER (line, model, part_no);
    PRINT 'Index IX_Parameter_HealthCheck created successfully.';
END
ELSE
BEGIN
    PRINT 'Index IX_Parameter_HealthCheck already exists.';
END
GO

IF OBJECT_ID('dbo.sp_GetSchedules', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_GetSchedules;
GO
CREATE PROCEDURE dbo.sp_GetSchedules
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        id,
        line,
        shift_name,
        CONVERT(VARCHAR(8), start_time, 108) AS start_time,
        CONVERT(VARCHAR(8), end_time, 108) AS end_time,
        planned_break_minutes,
        is_active
    FROM
        dbo.LINE_SCHEDULES
    ORDER BY
        line, shift_name;
END
GO
PRINT 'Stored Procedure sp_GetSchedules created/updated successfully.';

IF OBJECT_ID('dbo.sp_SaveSchedule', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_SaveSchedule;
GO
CREATE PROCEDURE dbo.sp_SaveSchedule
    @id INT,
    @line VARCHAR(50),
    @shift_name VARCHAR(50),
    @start_time TIME,
    @end_time TIME,
    @planned_break_minutes INT,
    @is_active BIT
AS
BEGIN
    SET NOCOUNT ON;
    IF @id = 0
    BEGIN
        INSERT INTO dbo.LINE_SCHEDULES (line, shift_name, start_time, end_time, planned_break_minutes, is_active)
        VALUES (@line, @shift_name, @start_time, @end_time, @planned_break_minutes, @is_active);
    END
    ELSE
    BEGIN
        UPDATE dbo.LINE_SCHEDULES
        SET line = @line,
            shift_name = @shift_name,
            start_time = @start_time,
            end_time = @end_time,
            planned_break_minutes = @planned_break_minutes,
            is_active = @is_active
        WHERE id = @id;
    END
END
GO
PRINT 'Stored Procedure sp_SaveSchedule created/updated successfully.';

IF OBJECT_ID('dbo.sp_DeleteSchedule', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_DeleteSchedule;
GO
CREATE PROCEDURE dbo.sp_DeleteSchedule
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM dbo.LINE_SCHEDULES WHERE id = @id;
END
GO
PRINT 'Stored Procedure sp_DeleteSchedule created/updated successfully.';

IF OBJECT_ID('dbo.sp_GetMissingParameters', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_GetMissingParameters;
GO
CREATE PROCEDURE dbo.sp_GetMissingParameters
AS
BEGIN
    SET NOCOUNT ON;
    ;WITH DistinctPartsToCheck AS (
        SELECT DISTINCT
            line,
            model,
            part_no
        FROM
            dbo.IOT_TOOLBOX_PARTS
    )
    SELECT
        p.line,
        p.model,
        p.part_no
    FROM
        DistinctPartsToCheck p
    WHERE
        NOT EXISTS (
            SELECT 1
            FROM dbo.IOT_TOOLBOX_PARAMETER param
            WHERE
                param.line = p.line
                AND param.model = p.model
                AND param.part_no = p.part_no
        )
    ORDER BY
        p.line, p.model, p.part_no;
END
GO
PRINT 'Stored Procedure sp_GetMissingParameters created/updated successfully.';

IF OBJECT_ID('dbo.sp_CalculateOEE_PieChart', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_CalculateOEE_PieChart;
GO
CREATE PROCEDURE dbo.sp_CalculateOEE_PieChart
    @StartDate DATE,
    @EndDate DATE,
    @Line VARCHAR(50) = NULL,
    @Model VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @TotalPlannedMinutes INT;
    WITH ActualProductionShifts AS (
        SELECT DISTINCT p.log_date, s.id AS schedule_id
        FROM dbo.IOT_TOOLBOX_PARTS p
        JOIN dbo.LINE_SCHEDULES s ON p.line = s.line AND s.is_active = 1
        WHERE p.log_date BETWEEN @StartDate AND @EndDate
          AND (@Line IS NULL OR p.line = @Line)
          AND (
               (s.start_time <= s.end_time AND p.log_time BETWEEN s.start_time AND s.end_time) OR
               (s.start_time > s.end_time AND (p.log_time >= s.start_time OR p.log_time < s.end_time))
          )
    )
    SELECT @TotalPlannedMinutes = ISNULL(SUM(
        CASE WHEN s.end_time >= s.start_time THEN DATEDIFF(MINUTE, s.start_time, s.end_time)
             ELSE DATEDIFF(MINUTE, s.start_time, s.end_time) + 1440
        END - s.planned_break_minutes
    ), 0)
    FROM ActualProductionShifts aps
    JOIN dbo.LINE_SCHEDULES s ON aps.schedule_id = s.id;

    IF @TotalPlannedMinutes <= 0
    BEGIN
        SELECT Quality = 0, Availability = 0, Performance = 0, OEE = 0,
               FG = 0, Defects = 0, NG = 0, Rework = 0, Hold = 0, Scrap = 0, Etc = 0,
               Runtime = 0, PlannedTime = 0, Downtime = 0,
               ActualOutput = 0, TotalTheoreticalMinutes = 0;
        RETURN;
    END

    DECLARE @TotalDowntimeMinutes INT;
    SELECT @TotalDowntimeMinutes = ISNULL(SUM(DATEDIFF(MINUTE, stop_begin, stop_end)), 0)
    FROM dbo.IOT_TOOLBOX_STOP_CAUSES
    WHERE log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR line = @Line);

    DECLARE @TotalRuntimeMinutes INT;
    SET @TotalRuntimeMinutes = @TotalPlannedMinutes - @TotalDowntimeMinutes;
    IF @TotalRuntimeMinutes < 0 SET @TotalRuntimeMinutes = 0;

    DECLARE @TotalFG INT, @TotalDefects INT, @TotalActualOutput INT, @TotalTheoreticalMinutes DECIMAL(18, 4);
    DECLARE @TotalNG INT, @TotalREWORK INT, @TotalHOLD INT, @TotalSCRAP INT, @TotalETC INT;

    SELECT
        @TotalFG = ISNULL(SUM(CASE WHEN p.count_type = 'FG' THEN p.count_value ELSE 0 END), 0),
        @TotalNG = ISNULL(SUM(CASE WHEN p.count_type = 'NG' THEN p.count_value ELSE 0 END), 0),
        @TotalREWORK = ISNULL(SUM(CASE WHEN p.count_type = 'REWORK' THEN p.count_value ELSE 0 END), 0),
        @TotalHOLD = ISNULL(SUM(CASE WHEN p.count_type = 'HOLD' THEN p.count_value ELSE 0 END), 0),
        @TotalSCRAP = ISNULL(SUM(CASE WHEN p.count_type = 'SCRAP' THEN p.count_value ELSE 0 END), 0),
        @TotalETC = ISNULL(SUM(CASE WHEN p.count_type = 'ETC.' THEN p.count_value ELSE 0 END), 0),
        @TotalTheoreticalMinutes = ISNULL(SUM( p.count_value * (60.0 / NULLIF(param.planned_output, 0)) ), 0)
    FROM dbo.IOT_TOOLBOX_PARTS p
    JOIN dbo.IOT_TOOLBOX_PARAMETER param ON p.line = param.line AND p.model = param.model AND p.part_no = param.part_no
    WHERE p.log_date BETWEEN @StartDate AND @EndDate
        AND (@Line IS NULL OR p.line = @Line)
        AND (@Model IS NULL OR p.model = @Model)
        AND param.planned_output > 0;

    SET @TotalDefects = @TotalNG + @TotalREWORK + @TotalHOLD + @TotalSCRAP + @TotalETC;
    SET @TotalActualOutput = @TotalFG + @TotalDefects;

    SELECT
        Quality = CAST(ISNULL((@TotalFG * 100.0) / NULLIF(@TotalActualOutput, 0), 0) AS DECIMAL(5,1)),
        Availability = CAST(ISNULL((@TotalRuntimeMinutes * 100.0) / NULLIF(@TotalPlannedMinutes, 0), 0) AS DECIMAL(5,1)),
        Performance = CAST(ISNULL((@TotalTheoreticalMinutes * 100.0) / NULLIF(@TotalRuntimeMinutes, 0), 0) AS DECIMAL(5,1)),
        OEE = CAST(ISNULL((((@TotalRuntimeMinutes * 1.0) / NULLIF(@TotalPlannedMinutes, 0)) * ((@TotalTheoreticalMinutes * 1.0) / NULLIF(@TotalRuntimeMinutes, 0)) * ((@TotalFG * 1.0) / NULLIF(@TotalActualOutput, 0))) * 100, 0) AS DECIMAL(5,1)),
        FG = @TotalFG,
        Defects = @TotalDefects,
        NG = @TotalNG,
        Rework = @TotalREWORK,
        Hold = @TotalHOLD,
        Scrap = @TotalSCRAP,
        Etc = @TotalETC,
        Runtime = @TotalRuntimeMinutes,
        PlannedTime = @TotalPlannedMinutes,
        Downtime = @TotalDowntimeMinutes,
        ActualOutput = @TotalActualOutput,
        TotalTheoreticalMinutes = CAST(@TotalTheoreticalMinutes AS DECIMAL(18,2));
END
GO
PRINT 'Stored Procedure sp_CalculateOEE_PieChart created/updated successfully.';

IF OBJECT_ID('dbo.sp_CalculateOEE_LineChart', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_CalculateOEE_LineChart;
GO
CREATE PROCEDURE dbo.sp_CalculateOEE_LineChart
    @StartDate DATE,
    @EndDate DATE,
    @Line VARCHAR(50) = NULL,
    @Model VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @DateSeries TABLE (LogDate DATE PRIMARY KEY);
    DECLARE @CurrentDate DATE = @StartDate;
    WHILE @CurrentDate <= @EndDate
    BEGIN
        INSERT INTO @DateSeries (LogDate) VALUES (@CurrentDate);
        SET @CurrentDate = DATEADD(DAY, 1, @CurrentDate);
    END;

    WITH
    DailyPlannedTime AS (
        SELECT
            aps.log_date AS LogDate,
            ISNULL(SUM(
                CASE WHEN s.end_time >= s.start_time THEN DATEDIFF(MINUTE, s.start_time, s.end_time)
                     ELSE DATEDIFF(MINUTE, s.start_time, s.end_time) + 1440
                END - s.planned_break_minutes
            ), 0) AS PlannedMinutes
        FROM (
            SELECT DISTINCT p.log_date, s.id AS schedule_id
            FROM dbo.IOT_TOOLBOX_PARTS p
            JOIN dbo.LINE_SCHEDULES s ON p.line = s.line AND s.is_active = 1
            WHERE p.log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR p.line = @Line)
              AND (
                   (s.start_time <= s.end_time AND p.log_time BETWEEN s.start_time AND s.end_time) OR
                   (s.start_time > s.end_time AND (p.log_time >= s.start_time OR p.log_time < s.end_time))
              )
        ) aps
        JOIN dbo.LINE_SCHEDULES s ON aps.schedule_id = s.id
        GROUP BY aps.log_date
    ),
    DailyDowntime AS (
        SELECT
            log_date,
            ISNULL(SUM(DATEDIFF(MINUTE, stop_begin, stop_end)), 0) AS DowntimeMinutes
        FROM dbo.IOT_TOOLBOX_STOP_CAUSES
        WHERE log_date BETWEEN @StartDate AND @EndDate AND (@Line IS NULL OR line = @Line)
        GROUP BY log_date
    ),
    DailyProduction AS (
        SELECT
            p.log_date,
            SUM(CASE WHEN p.count_type = 'FG' THEN p.count_value ELSE 0 END) AS TotalFG,
            SUM(CASE WHEN p.count_type <> 'FG' THEN p.count_value ELSE 0 END) AS TotalDefects,
            SUM(p.count_value * (60.0 / NULLIF(param.planned_output, 0))) AS TheoreticalMinutes
        FROM dbo.IOT_TOOLBOX_PARTS p
        JOIN dbo.IOT_TOOLBOX_PARAMETER param ON p.line = param.line AND p.model = param.model AND p.part_no = param.part_no
        WHERE p.log_date BETWEEN @StartDate AND @EndDate
            AND (@Line IS NULL OR p.line = @Line)
            AND (@Model IS NULL OR p.model = @Model)
            AND param.planned_output > 0
        GROUP BY p.log_date
    )
    SELECT
        d.LogDate AS [date],
        CAST(ISNULL((dp.TotalFG * 100.0) / NULLIF(dp.TotalFG + dp.TotalDefects, 0), 0) AS DECIMAL(5,1)) AS quality,
        CAST(ISNULL(((ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0)) * 100.0) / NULLIF(dpt.PlannedMinutes, 0), 0) AS DECIMAL(5,1)) AS availability,
        CAST(ISNULL((dp.TheoreticalMinutes * 100.0) / NULLIF(ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0), 0), 0) AS DECIMAL(5,1)) AS performance,
        CAST(ISNULL(
                (((ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0)) * 1.0) / NULLIF(dpt.PlannedMinutes, 0)) * ((dp.TheoreticalMinutes * 1.0) / NULLIF(ISNULL(dpt.PlannedMinutes, 0) - ISNULL(dd.DowntimeMinutes, 0), 0)) * ((dp.TotalFG * 1.0) / NULLIF(dp.TotalFG + dp.TotalDefects, 0)) * 100
            , 0) 
        AS DECIMAL(5,1)) AS oee
    FROM @DateSeries d
    LEFT JOIN DailyPlannedTime dpt ON d.LogDate = dpt.LogDate
    LEFT JOIN DailyDowntime dd ON d.LogDate = dd.log_date
    LEFT JOIN DailyProduction dp ON d.LogDate = dp.log_date
    ORDER BY d.LogDate ASC;
END
GO
PRINT 'Stored Procedure sp_CalculateOEE_LineChart created/updated successfully.';

IF NOT EXISTS (SELECT 1 FROM dbo.LINE_SCHEDULES)
BEGIN
    PRINT 'Inserting sample data into LINE_SCHEDULES...';
    INSERT INTO dbo.LINE_SCHEDULES (line, shift_name, start_time, end_time, planned_break_minutes, is_active)
    VALUES
    ('ASSEMBLY', 'DAY', '08:00:00', '17:00:00', 75, 1),
    ('ASSEMBLY', 'NIGHT', '20:00:00', '05:00:00', 75, 1),
    ('PAINT', 'DAY', '07:00:00', '16:00:00', 60, 1),
    ('SPOT', 'DAY', '08:00:00', '17:00:00', 60, 1),
    ('BEND', 'DAY', '08:00:00', '17:00:00', 60, 1),
    ('PRESS', 'DAY', '08:00:00', '17:00:00', 60, 1);
    PRINT 'Sample data inserted successfully.';
END
ELSE
BEGIN
    PRINT 'Sample data already exists in LINE_SCHEDULES.';
END
GO

PRINT 'OEE System Enhancement Script completed successfully.';
GO
